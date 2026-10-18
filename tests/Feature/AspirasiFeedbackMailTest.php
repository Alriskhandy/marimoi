<?php

namespace Tests\Feature;

use App\Mail\AspirasiMail;
use App\Mail\TanggapanMail;
use App\Models\Aspirasi;
use App\Models\DataSpatial;
use App\Models\KategoriAspirasi;
use App\Models\LegacyCategory as Category;
use App\Models\Opd;
use App\Models\Permission;
use App\Models\ProjectFeedback;
use App\Models\Role;
use App\Models\User;
use App\Support\TicketLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/**
 * Email aspirasi masyarakat & feedback data spasial: penerima, isi, dan ketahanan
 * terhadap kegagalan mailer.
 */
class AspirasiFeedbackMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('public');
        Http::fake(['hcaptcha.com/*' => Http::response(['success' => true])]);
        config(['mail.admin_addresses' => ['bappeda@example.com']]);
    }

    private function userWithRole(string $slug, array $permissions = [], array $attributes = []): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug, 'description' => null]);
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        return User::factory()->create(array_merge(['role_id' => $role->id, 'is_active' => true], $attributes));
    }

    /**
     * @return array<string, mixed>
     */
    private function kritikSaranPayload(array $overrides = []): array
    {
        return array_merge([
            'nama_pengirim' => 'Citra Lestari',
            'email' => 'citra@example.com',
            'phone' => '081211112222',
            'alamat' => 'Jl. Contoh No. 3',
            'jenis_aspirasi' => 'kritik & saran',
            'judul_aspirasi' => 'Saran layanan',
            'isi_aspirasi' => 'Mohon layanan lebih cepat direspons.',
            'agreement' => true,
            'h-captcha-response' => 'dummy-token',
        ], $overrides);
    }

    private function submitAspirasi(array $payload): TestResponse
    {
        return $this->postJson(route('aspirasi-masyarakat.store'), $payload, ['X-Requested-With' => 'XMLHttpRequest']);
    }

    private function dataSpatialOwnedBy(?User $owner): DataSpatial
    {
        $category = Category::firstOrCreate(['nama' => 'Kategori Uji'], ['type' => 'tematik', 'warna' => '#000']);

        return DataSpatial::factory()->create([
            'user_id' => $owner?->id,
            'kategori_id' => $category->id,
            'deskripsi' => 'Proyek Uji',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function feedbackPayload(DataSpatial $dataSpatial): array
    {
        return [
            'data_spatial_id' => $dataSpatial->id,
            'nama_pemberi_aspirasi' => 'Rahmat Hidayat',
            'email' => 'rahmat@example.com',
            'phone' => '081233334444',
            'nama_proyek' => 'Jalan Lingkar Ternate',
            'kabupaten_kota' => 'Ternate',
            'kecamatan' => 'Ternate Utara',
            'jenis_tanggapan' => 'saran',
            'tanggapan' => 'Mohon ditambah lampu jalan.',
            'h-captcha-response' => 'dummy-token',
        ];
    }

    public function test_kritik_saran_emails_citizen_and_configured_admins_but_not_opd(): void
    {
        KategoriAspirasi::create(['nama_kategori' => 'Kritik dan Saran']);

        $ticket = $this->submitAspirasi($this->kritikSaranPayload())->assertCreated()->json('data.nomor_tiket');

        Mail::assertQueued(AspirasiMail::class, fn (AspirasiMail $mail) => $mail->hasTo('citra@example.com')
            && $mail->type === AspirasiMail::TYPE_PENERIMAAN
            && $mail->data['nomor_tiket'] === $ticket);
        Mail::assertQueued(AspirasiMail::class, fn (AspirasiMail $mail) => $mail->hasTo('bappeda@example.com')
            && $mail->type === AspirasiMail::TYPE_ADMIN);
        Mail::assertNotQueued(AspirasiMail::class, fn (AspirasiMail $mail) => $mail->type === AspirasiMail::TYPE_OPD);
        Mail::assertQueuedCount(2);
    }

    public function test_usulan_also_emails_the_target_opd(): void
    {
        $opd = Opd::create(['name' => 'Dinas PUPR', 'singkatan' => 'PUPR', 'email' => 'pupr@example.com']);
        $kategori = KategoriAspirasi::create(['nama_kategori' => 'Infrastruktur', 'opd_id' => $opd->id]);

        $this->submitAspirasi($this->kritikSaranPayload([
            'jenis_aspirasi' => 'usulan',
            'kategori_aspirasi_id' => $kategori->id,
            'latitude' => 0.79,
            'longitude' => 127.38,
            'lampiran' => UploadedFile::fake()->image('foto.jpg'),
        ]))->assertCreated();

        // admin_id adalah FK ke users — dulu terisi ID OPD dan memicu foreign key violation.
        $this->assertNull(Aspirasi::firstOrFail()->admin_id);

        Mail::assertQueued(AspirasiMail::class, fn (AspirasiMail $mail) => $mail->hasTo('pupr@example.com')
            && $mail->type === AspirasiMail::TYPE_OPD
            && $mail->data['opd_terkait'] === 'PUPR');
        Mail::assertQueuedCount(3);
    }

    public function test_admin_recipients_fall_back_to_active_super_admins(): void
    {
        config(['mail.admin_addresses' => []]);
        $this->userWithRole('super-admin', [], ['email' => 'root@example.com']);
        $this->userWithRole('super-admin', [], ['email' => 'nonaktif@example.com', 'is_active' => false]);
        $this->userWithRole('admin-opd', [], ['email' => 'opd@example.com']);
        KategoriAspirasi::create(['nama_kategori' => 'Kritik dan Saran']);

        $this->submitAspirasi($this->kritikSaranPayload())->assertCreated();

        Mail::assertQueued(AspirasiMail::class, fn (AspirasiMail $mail) => $mail->hasTo('root@example.com')
            && $mail->type === AspirasiMail::TYPE_ADMIN);
        Mail::assertNotQueued(AspirasiMail::class, fn (AspirasiMail $mail) => $mail->hasTo('nonaktif@example.com')
            || $mail->hasTo('opd@example.com'));
    }

    public function test_mailer_failure_does_not_break_submission(): void
    {
        KategoriAspirasi::create(['nama_kategori' => 'Kritik dan Saran']);
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

        $this->submitAspirasi($this->kritikSaranPayload())->assertCreated();

        $this->assertDatabaseHas('aspirasi', ['email' => 'citra@example.com', 'status' => 'pending']);
    }

    public function test_public_submissions_are_rate_limited_with_indonesian_message(): void
    {
        KategoriAspirasi::create(['nama_kategori' => 'Kritik dan Saran']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->submitAspirasi($this->kritikSaranPayload())->assertCreated();
        }

        $this->submitAspirasi($this->kritikSaranPayload())
            ->assertTooManyRequests()
            ->assertJson(['status' => 'error'])
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'Terlalu banyak kiriman'));
    }

    public function test_feedback_emails_citizen_with_ticket_and_data_owner(): void
    {
        $owner = $this->userWithRole('admin-opd', [], ['email' => 'pemilik@example.com']);
        $dataSpatial = $this->dataSpatialOwnedBy($owner);

        $this->postJson(route('feedback.store'), $this->feedbackPayload($dataSpatial))->assertOk();

        $feedback = ProjectFeedback::firstOrFail();
        Mail::assertQueued(TanggapanMail::class, fn (TanggapanMail $mail) => $mail->hasTo('rahmat@example.com')
            && $mail->type === 'penerimaan'
            && $mail->data['kode'] === $feedback->ticketCode());
        Mail::assertQueued(TanggapanMail::class, fn (TanggapanMail $mail) => $mail->hasTo('pemilik@example.com')
            && $mail->type === 'admin');
        Mail::assertNotQueued(TanggapanMail::class, fn (TanggapanMail $mail) => $mail->hasTo(config('mail.from.address')));
    }

    public function test_feedback_without_data_owner_notifies_admins_instead_of_sender_address(): void
    {
        $this->postJson(route('feedback.store'), $this->feedbackPayload($this->dataSpatialOwnedBy(null)))->assertOk();

        Mail::assertQueued(TanggapanMail::class, fn (TanggapanMail $mail) => $mail->hasTo('bappeda@example.com')
            && $mail->type === 'admin');
        Mail::assertQueuedCount(2);
    }

    public function test_aspirasi_status_update_emails_citizen_only_when_something_changed(): void
    {
        $admin = $this->userWithRole('admin-bappeda', ['aspirasi.edit']);
        $aspirasi = Aspirasi::create([
            'kategori_aspirasi_id' => KategoriAspirasi::create(['nama_kategori' => 'Infrastruktur'])->id,
            'nama_pengirim' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'alamat' => 'Jl. Uji Coba No. 1',
            'jenis_aspirasi' => 'usulan',
            'judul_aspirasi' => 'Perbaikan Jalan Rusak',
            'isi_aspirasi' => 'Jalan di depan rumah saya rusak parah.',
            'status' => 'pending',
        ]);
        $payload = ['status' => 'diproses', 'tanggapan_admin' => 'Sedang disurvei tim lapangan.'];

        $ajax = ['X-Requested-With' => 'XMLHttpRequest'];
        $this->actingAs($admin)->putJson(route('aspirasi.updateStatus', $aspirasi), $payload, $ajax)->assertOk();
        $this->actingAs($admin)->putJson(route('aspirasi.updateStatus', $aspirasi), $payload, $ajax)->assertOk();

        Mail::assertQueuedCount(1);
        Mail::assertQueued(TanggapanMail::class, fn (TanggapanMail $mail) => $mail->hasTo('budi@example.com')
            && $mail->type === 'diproses'
            && $mail->context === TanggapanMail::CONTEXT_ASPIRASI
            && $mail->data['kode'] === $aspirasi->nomor_tiket);
    }

    public function test_feedback_respond_does_not_resend_when_nothing_changed(): void
    {
        $admin = $this->userWithRole('super-admin', ['project-feedbacks.respond']);
        $feedback = ProjectFeedback::factory()->create([
            'data_spatial_id' => $this->dataSpatialOwnedBy($admin)->id,
            'laporan_gambar' => null,
            'status' => 'pending',
            'email' => 'warga@example.com',
        ]);
        $payload = ['status' => 'selesai', 'response_admin' => 'Sudah ditindaklanjuti oleh tim lapangan'];

        $this->actingAs($admin)->putJson(route('project-feedbacks.respond', $feedback->id), $payload)->assertOk();
        $this->actingAs($admin)->putJson(route('project-feedbacks.respond', $feedback->id), $payload)->assertOk();

        Mail::assertQueuedCount(1);
    }

    public function test_citizen_receipt_renders_ticket_tracking_link_and_text_part(): void
    {
        $mail = new AspirasiMail([
            'id_aspirasi' => 7,
            'nomor_tiket' => 'MARIMOI-ASP-20261018-0001',
            'nama_pengirim' => 'Citra',
            'email' => 'citra@example.com',
            'jenis_aspirasi' => 'kritik & saran',
            'judul_aspirasi' => 'Saran layanan',
            'tanggal' => '18-10-2026 09:00:00',
        ], AspirasiMail::TYPE_PENERIMAAN);

        $mail->assertHasSubject('Aspirasi Anda Telah Kami Terima (MARIMOI-ASP-20261018-0001)');
        $mail->assertSeeInHtml('MARIMOI-ASP-20261018-0001');
        $mail->assertSeeInHtml(TicketLookup::trackingUrl('MARIMOI-ASP-20261018-0001'), false);
        $mail->assertSeeInOrderInHtml(['Cara melacak status', 'Masukkan nomor tiket MARIMOI-ASP-20261018-0001.', 'Cek Status']);
        $mail->assertSeeInText('Nomor Tiket: MARIMOI-ASP-20261018-0001');
        $mail->assertSeeInText('2. Masukkan nomor tiket MARIMOI-ASP-20261018-0001.');
        $this->assertFalse($mail->hasReplyTo('citra@example.com'));
    }

    public function test_admin_notification_replies_to_citizen_and_escapes_user_input(): void
    {
        $mail = new AspirasiMail([
            'id_aspirasi' => 7,
            'nomor_tiket' => 'MARIMOI-ASP-20261018-0001',
            'nama_pengirim' => 'Citra',
            'email' => 'citra@example.com',
            'isi_aspirasi' => '<script>alert(1)</script>',
            'kategori_aspirasi' => 'N/A',
        ], AspirasiMail::TYPE_ADMIN);

        $mail->assertHasReplyTo('citra@example.com');
        $mail->assertSeeInHtml(route('aspirasi.show', 7), false);
        $mail->assertDontSeeInHtml('<script>alert(1)</script>', false);
        $mail->assertSeeInHtml('&lt;script&gt;', false);
        $mail->assertDontSeeInHtml('N/A');
    }

    public function test_aspirasi_status_mail_speaks_about_aspirasi_not_tanggapan(): void
    {
        $mail = new TanggapanMail([
            'nama' => 'Budi',
            'kode' => 'MARIMOI-ASP-20261018-0002',
            'tanggapan_admin' => 'Usulan diteruskan ke Dinas PUPR.',
        ], 'selesai', TanggapanMail::CONTEXT_ASPIRASI);

        $mail->assertHasSubject('Tindak Lanjut Aspirasi Anda Telah Selesai (MARIMOI-ASP-20261018-0002)');
        $mail->assertSeeInHtml('Usulan diteruskan ke Dinas PUPR.');
        $mail->assertSeeInHtml('Lacak Status Aspirasi');
    }

    public function test_feedback_receipt_contains_ticket_and_tracking_guide(): void
    {
        $mail = new TanggapanMail([
            'kode' => 'MARIMOI-FDB-20261018-0042',
            'nama' => 'Rahmat',
            'email' => 'rahmat@example.com',
            'nama_proyek' => 'Jalan Lingkar Ternate',
            'jenis_tanggapan' => 'saran',
        ], 'penerimaan');

        $mail->assertHasSubject('Tanggapan Anda Telah Kami Terima (MARIMOI-FDB-20261018-0042)');
        $mail->assertSeeInHtml('MARIMOI-FDB-20261018-0042');
        $mail->assertSeeInHtml('Cara melacak status');
        $mail->assertSeeInHtml('Lacak Status Tanggapan');
        $mail->assertSeeInHtml(TicketLookup::trackingUrl('MARIMOI-FDB-20261018-0042'), false);
        $mail->assertSeeInText('1. Buka halaman Lacak Status');
    }

    public function test_status_updates_link_to_tracking_without_repeating_the_guide(): void
    {
        $mail = new TanggapanMail(['nama' => 'Rahmat', 'kode' => 'MARIMOI-FDB-20261018-0042'], 'diproses');

        $mail->assertSeeInHtml('Lacak Status Tanggapan');
        $mail->assertDontSeeInHtml('Cara melacak status');
    }

    public function test_admin_notifications_have_no_tracking_guide(): void
    {
        $mail = new AspirasiMail(['nomor_tiket' => 'MARIMOI-ASP-20261018-0001', 'id_aspirasi' => 7], AspirasiMail::TYPE_ADMIN);

        $mail->assertDontSeeInHtml('Cara melacak status');
    }

    public function test_email_header_embeds_marimoi_logo_inline(): void
    {
        $this->assertFileExists(public_path('frontend/img/logo/logo-white-email.png'));

        $mail = new AspirasiMail(['nomor_tiket' => 'MARIMOI-ASP-20261018-0001'], AspirasiMail::TYPE_PENERIMAAN);

        // Preview/assertion merender gambar sematan sebagai data: URI.
        $mail->assertSeeInHtml('alt="Logo MARIMOI"', false);
        $mail->assertSeeInHtml('src="data:image/png;base64,', false);
        $mail->assertSeeInHtml('Bappeda Maluku Utara');
    }

    public function test_sent_email_carries_logo_as_inline_attachment(): void
    {
        // Mail::fake() aktif di setUp; pakai MailManager baru (transport array) agar pesan benar-benar dibangun.
        $mailer = (new MailManager(app()))->mailer('array');
        $mailer->to('warga@example.com')->sendNow(
            new AspirasiMail(['nomor_tiket' => 'MARIMOI-ASP-20261018-0001'], AspirasiMail::TYPE_PENERIMAAN)
        );

        $message = $mailer->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $inline = collect($message->getAttachments())->filter(fn ($part) => $part->getDisposition() === 'inline');

        $this->assertCount(1, $inline);
        $this->assertSame('image/png', $inline->first()->getMediaType().'/'.$inline->first()->getMediaSubtype());
        $this->assertStringContainsString('src="cid:', $message->getHtmlBody());
    }
}
