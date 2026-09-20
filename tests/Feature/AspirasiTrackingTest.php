<?php

namespace Tests\Feature;

use App\Models\Aspirasi;
use App\Models\KategoriAspirasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AspirasiTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function kategori(): KategoriAspirasi
    {
        return KategoriAspirasi::create(['nama_kategori' => 'Infrastruktur']);
    }

    private function aspirasi(array $overrides = []): Aspirasi
    {
        return Aspirasi::create(array_merge([
            'kategori_aspirasi_id' => $this->kategori()->id,
            'nama_pengirim' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'alamat' => 'Jl. Uji Coba No. 1',
            'jenis_aspirasi' => 'usulan',
            'judul_aspirasi' => 'Perbaikan Jalan Rusak',
            'isi_aspirasi' => 'Jalan di depan rumah saya rusak parah.',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_guest_can_open_tracking_form(): void
    {
        $this->get(route('aspirasi-masyarakat.lacak'))
            ->assertOk()
            ->assertSee('Lacak Status Aspirasi');
    }

    public function test_matching_ticket_and_email_shows_status(): void
    {
        $aspirasi = $this->aspirasi([
            'status' => 'diproses',
            'tanggapan_admin' => 'Sedang kami tinjau di lapangan.',
        ]);

        $this->post(route('aspirasi-masyarakat.lacak.cari'), [
            'nomor_tiket' => $aspirasi->nomor_tiket,
            'kontak' => 'budi@example.com',
        ])
            ->assertOk()
            ->assertSee('Perbaikan Jalan Rusak')
            ->assertSee('Diproses')
            ->assertSee('Sedang kami tinjau di lapangan.')
            ->assertDontSee('Nomor tiket tidak ditemukan', false);
    }

    public function test_matching_ticket_and_differently_formatted_phone_still_matches(): void
    {
        $aspirasi = $this->aspirasi(['phone' => '081234567890', 'email' => 'lain@example.com']);

        $this->post(route('aspirasi-masyarakat.lacak.cari'), [
            'nomor_tiket' => $aspirasi->nomor_tiket,
            'kontak' => '+62 812-3456-7890',
        ])
            ->assertOk()
            ->assertSee('Perbaikan Jalan Rusak');
    }

    public function test_correct_ticket_with_wrong_contact_shows_generic_not_found(): void
    {
        $aspirasi = $this->aspirasi();

        $response = $this->post(route('aspirasi-masyarakat.lacak.cari'), [
            'nomor_tiket' => $aspirasi->nomor_tiket,
            'kontak' => 'bukan-pemilik@example.com',
        ]);

        $response->assertOk()
            ->assertSee('Nomor tiket tidak ditemukan, atau email/nomor WhatsApp tidak cocok', false)
            ->assertDontSee('Perbaikan Jalan Rusak');
    }

    public function test_nonexistent_ticket_shows_identical_generic_message(): void
    {
        $response = $this->post(route('aspirasi-masyarakat.lacak.cari'), [
            'nomor_tiket' => 'MARIMOI-ASP-99999999-9999',
            'kontak' => 'siapa@example.com',
        ]);

        $response->assertOk()
            ->assertSee('Nomor tiket tidak ditemukan, atau email/nomor WhatsApp tidak cocok', false);
    }

    public function test_tracking_search_is_rate_limited(): void
    {
        $payload = ['nomor_tiket' => 'MARIMOI-ASP-99999999-9999', 'kontak' => 'siapa@example.com'];

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('aspirasi-masyarakat.lacak.cari'), $payload)->assertOk();
        }

        $this->post(route('aspirasi-masyarakat.lacak.cari'), $payload)
            ->assertStatus(429);
    }

    public function test_rejected_aspirasi_shows_rejection_message_without_progress_steps(): void
    {
        $aspirasi = $this->aspirasi(['status' => 'ditolak']);

        $this->post(route('aspirasi-masyarakat.lacak.cari'), [
            'nomor_tiket' => $aspirasi->nomor_tiket,
            'kontak' => 'budi@example.com',
        ])
            ->assertOk()
            ->assertSee('Aspirasi ini tidak dapat kami tindak lanjuti')
            ->assertDontSee('Menampilkan status terkini');
    }

    public function test_aspirasi_without_response_shows_fallback_message(): void
    {
        $aspirasi = $this->aspirasi(['tanggapan_admin' => null]);

        $this->post(route('aspirasi-masyarakat.lacak.cari'), [
            'nomor_tiket' => $aspirasi->nomor_tiket,
            'kontak' => 'budi@example.com',
        ])
            ->assertOk()
            ->assertSee('Belum ada tanggapan. Mohon tunggu, tim kami akan segera memproses.');
    }

    public function test_submit_response_still_includes_ticket_number(): void
    {
        Mail::fake();
        Http::fake(['hcaptcha.com/*' => Http::response(['success' => true])]);
        // aspirasiStore() mencari kategori "Kritik dan Saran" berdasarkan nama untuk jenis
        // "kritik & saran" (bukan hardcode ID) — wajib ada agar submit tidak gagal.
        KategoriAspirasi::create(['nama_kategori' => 'Kritik dan Saran']);

        $response = $this->postJson(route('aspirasi-masyarakat.store'), [
            'nama_pengirim' => 'Ani Wijaya',
            'email' => 'ani@example.com',
            'phone' => '081298765432',
            'alamat' => 'Jl. Contoh No. 2',
            'jenis_aspirasi' => 'kritik & saran',
            'judul_aspirasi' => 'Saran layanan',
            'isi_aspirasi' => 'Mohon layanan lebih cepat direspons.',
            'agreement' => true,
            'h-captcha-response' => 'dummy-token',
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['nomor_tiket']]);
        $this->assertNotEmpty($response->json('data.nomor_tiket'));
    }
}
