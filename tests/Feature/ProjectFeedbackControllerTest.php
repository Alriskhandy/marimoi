<?php

namespace Tests\Feature;

use App\Mail\TanggapanMail;
use App\Models\DataSpatial;
use App\Models\LegacyCategory as Category;
use App\Models\Opd;
use App\Models\Permission;
use App\Models\ProjectFeedback;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectFeedbackControllerTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $slug, ?int $opdId = null): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug, 'description' => null]);
        foreach (['project-feedbacks.view', 'project-feedbacks.respond', 'project-feedbacks.delete'] as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        return User::factory()->create(['role_id' => $role->id, 'opd_id' => $opdId]);
    }

    private function opd(string $name): Opd
    {
        return Opd::create(['name' => $name, 'singkatan' => $name]);
    }

    private function dataSpatialFor(User $owner, string $dataType = 'proyek_strategis'): DataSpatial
    {
        $category = Category::firstOrCreate(['nama' => 'Kategori Uji'], ['type' => 'tematik', 'warna' => '#000']);

        return DataSpatial::factory()->create([
            'user_id' => $owner->id,
            'kategori_id' => $category->id,
            'data_type' => $dataType,
            'deskripsi' => 'Proyek Uji '.$dataType,
        ]);
    }

    private function feedbackFor(User $owner, array $attributes = []): ProjectFeedback
    {
        return ProjectFeedback::factory()->create(array_merge([
            'data_spatial_id' => $this->dataSpatialFor($owner)->id,
            'opd_id' => $owner->opd_id,
            'laporan_gambar' => null,
        ], $attributes));
    }

    public function test_admin_sees_feedback_from_every_data_type(): void
    {
        $admin = $this->userWithRole('super-admin');
        $names = [];
        foreach (['tematik', 'proyek_strategis', 'pokir_dprd', 'usulan_musrenbang'] as $dataType) {
            $names[] = $this->feedbackFor($admin, [
                'data_spatial_id' => $this->dataSpatialFor($admin, $dataType)->id,
                'nama_pemberi_aspirasi' => 'Warga '.$dataType,
            ])->nama_pemberi_aspirasi;
        }

        $this->actingAs($admin)
            ->get(route('project-feedbacks.index'))
            ->assertOk()
            ->assertSee($names)
            ->assertViewHas('stats', fn (array $stats) => $stats['total'] === 4);
    }

    public function test_legacy_type_parameter_is_ignored(): void
    {
        $admin = $this->userWithRole('super-admin');
        $feedback = $this->feedbackFor($admin);

        $this->actingAs($admin)
            ->get(route('project-feedbacks.index', ['type' => 'psn']))
            ->assertOk()
            ->assertSee($feedback->nama_pemberi_aspirasi);
    }

    public function test_data_spatial_search_covers_every_data_type(): void
    {
        $admin = $this->userWithRole('super-admin');
        $tematik = $this->dataSpatialFor($admin, 'tematik');
        $proyek = $this->dataSpatialFor($admin, 'proyek_strategis');

        $this->actingAs($admin)
            ->getJson(route('project-feedbacks.data-spatial', ['search' => 'proyek uji']))
            ->assertOk()
            ->assertJsonPath('pagination.more', false)
            ->assertJsonFragment(['id' => $tematik->id, 'nama_proyek' => $tematik->deskripsi])
            ->assertJsonFragment(['id' => $proyek->id, 'nama_proyek' => $proyek->deskripsi]);
    }

    public function test_data_spatial_search_is_limited_to_own_data_for_opd_user(): void
    {
        $userA = $this->userWithRole('admin-opd', $this->opd('Dinas A')->id);
        $userB = $this->userWithRole('admin-opd', $this->opd('Dinas B')->id);
        $own = $this->dataSpatialFor($userA);
        $other = $this->dataSpatialFor($userB);

        $results = $this->actingAs($userA)
            ->getJson(route('project-feedbacks.data-spatial', ['search' => 'proyek']))
            ->assertOk()
            ->json('results');

        $this->assertSame([$own->id], array_column($results, 'id'));
        $this->assertNotContains($other->id, array_column($results, 'id'));
    }

    public function test_search_is_case_insensitive(): void
    {
        $admin = $this->userWithRole('super-admin');
        $this->feedbackFor($admin, ['nama_pemberi_aspirasi' => 'Budi Santoso']);
        $this->feedbackFor($admin, ['nama_pemberi_aspirasi' => 'Siti Aminah']);

        $this->actingAs($admin)
            ->get(route('project-feedbacks.index', ['search' => 'budi']))
            ->assertOk()
            ->assertViewHas('feedbacks', fn ($feedbacks) => $feedbacks->total() === 1
                && $feedbacks->first()->nama_pemberi_aspirasi === 'Budi Santoso');
    }

    public function test_opd_user_only_sees_feedback_for_own_opd_or_own_data(): void
    {
        $opdA = $this->opd('Dinas A');
        $opdB = $this->opd('Dinas B');
        $userA = $this->userWithRole('admin-opd', $opdA->id);
        $userB = $this->userWithRole('admin-opd', $opdB->id);

        $ownOpd = $this->feedbackFor($userA, ['nama_pemberi_aspirasi' => 'Untuk OPD A']);
        $ownDataWithoutOpd = $this->feedbackFor($userA, ['nama_pemberi_aspirasi' => 'Data milik A', 'opd_id' => null]);
        $otherOpd = $this->feedbackFor($userB, ['nama_pemberi_aspirasi' => 'Untuk OPD B']);

        $this->actingAs($userA)
            ->get(route('project-feedbacks.index'))
            ->assertOk()
            ->assertSee($ownOpd->nama_pemberi_aspirasi)
            ->assertSee($ownDataWithoutOpd->nama_pemberi_aspirasi)
            ->assertDontSee($otherOpd->nama_pemberi_aspirasi)
            ->assertViewHas('stats', fn (array $stats) => $stats['total'] === 2);
    }

    public function test_opd_user_cannot_access_feedback_of_other_opd(): void
    {
        $userA = $this->userWithRole('admin-opd', $this->opd('Dinas A')->id);
        $userB = $this->userWithRole('admin-opd', $this->opd('Dinas B')->id);
        $otherFeedback = $this->feedbackFor($userB);

        $this->actingAs($userA)->getJson(route('project-feedbacks.show', $otherFeedback->id))->assertNotFound();
        $this->actingAs($userA)
            ->putJson(route('project-feedbacks.respond', $otherFeedback->id), [
                'status' => 'selesai',
                'response_admin' => 'Mencoba menanggapi milik OPD lain',
            ])
            ->assertNotFound();
        $this->actingAs($userA)->deleteJson(route('project-feedbacks.destroy', $otherFeedback->id))->assertNotFound();

        $this->assertDatabaseHas('project_feedbacks', ['id' => $otherFeedback->id, 'status' => $otherFeedback->status]);
    }

    public function test_respond_updates_status_and_emails_stable_feedback_code(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('super-admin');
        $feedback = $this->feedbackFor($admin, [
            'status' => 'pending',
            'email' => 'warga@example.com',
            'created_at' => '2026-01-15 08:00:00',
        ]);

        $this->actingAs($admin)
            ->putJson(route('project-feedbacks.respond', $feedback->id), [
                'status' => 'selesai',
                'response_admin' => 'Sudah ditindaklanjuti oleh tim lapangan',
            ])
            ->assertOk();

        $this->assertDatabaseHas('project_feedbacks', ['id' => $feedback->id, 'status' => 'selesai']);
        Mail::assertQueued(TanggapanMail::class, fn (TanggapanMail $mail) => $mail->hasTo('warga@example.com')
            && str_starts_with($mail->data['kode'], 'MARIMOI-FDB-20260115-'));
    }

    public function test_respond_requires_valid_status_and_response(): void
    {
        $admin = $this->userWithRole('super-admin');
        $feedback = $this->feedbackFor($admin);

        $this->actingAs($admin)
            ->putJson(route('project-feedbacks.respond', $feedback->id), ['status' => 'pending', 'response_admin' => 'pendek'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'response_admin'], 'errors');
    }

    public function test_store_saves_image_to_public_attachment_folder_and_sets_owner_opd(): void
    {
        Storage::fake('public');
        $opd = $this->opd('Dinas Pemilik');
        $owner = $this->userWithRole('admin-opd', $opd->id);
        $admin = $this->userWithRole('super-admin');
        $dataSpatial = $this->dataSpatialFor($owner);

        $this->actingAs($admin)
            ->post(route('project-feedbacks.store'), [
                'data_spatial_id' => $dataSpatial->id,
                'nama_pemberi_aspirasi' => 'Warga Uji',
                'nama_proyek' => 'Proyek Uji',
                'kabupaten_kota' => 'Ternate',
                'tanggapan' => 'Jalan rusak di dekat proyek',
                'jenis_tanggapan' => 'keluhan',
                'laporan_gambar' => UploadedFile::fake()->image('laporan.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $feedback = ProjectFeedback::firstWhere('nama_pemberi_aspirasi', 'Warga Uji');
        $this->assertSame($opd->id, $feedback->opd_id);
        Storage::disk('public')->assertExists('aspirasi_lampiran/'.$feedback->laporan_gambar);
        $this->assertStringEndsWith('/storage/aspirasi_lampiran/'.$feedback->laporan_gambar, $feedback->image_url);
    }

    public function test_destroy_removes_feedback_and_its_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('aspirasi_lampiran/lama.jpg', 'gambar');
        $admin = $this->userWithRole('super-admin');
        $feedback = $this->feedbackFor($admin, ['laporan_gambar' => 'lama.jpg']);

        $this->actingAs($admin)->deleteJson(route('project-feedbacks.destroy', $feedback->id))->assertOk();

        $this->assertDatabaseMissing('project_feedbacks', ['id' => $feedback->id]);
        Storage::disk('public')->assertMissing('aspirasi_lampiran/lama.jpg');
    }

    public function test_spatial_feedback_feature_is_retired(): void
    {
        $this->assertFalse(Route::has('spatial-feedbacks.index'));
        $this->assertFalse(Schema::hasTable('spatial_feedbacks'));
        $this->assertNotContains('spatial-feedbacks.view', Permission::catalogNames());
    }

    public function test_index_filters_apply_across_pages_and_respect_per_page(): void
    {
        $admin = $this->userWithRole('super-admin');
        foreach (range(1, 12) as $number) {
            $this->feedbackFor($admin, ['status' => 'pending', 'nama_pemberi_aspirasi' => 'Pending '.$number]);
        }
        $this->feedbackFor($admin, ['status' => 'selesai', 'nama_pemberi_aspirasi' => 'Sudah Selesai']);

        $this->actingAs($admin)
            ->get(route('project-feedbacks.index', ['status' => 'selesai']))
            ->assertOk()
            ->assertSee('Sudah Selesai')
            ->assertViewHas('feedbacks', fn ($feedbacks) => $feedbacks->total() === 1);

        $this->actingAs($admin)
            ->get(route('project-feedbacks.index', ['per_page' => 25]))
            ->assertOk()
            ->assertViewHas('feedbacks', fn ($feedbacks) => $feedbacks->perPage() === 25 && $feedbacks->count() === 13);

        $this->actingAs($admin)
            ->get(route('project-feedbacks.index', ['per_page' => 9999]))
            ->assertViewHas('feedbacks', fn ($feedbacks) => $feedbacks->perPage() === 10);
    }

    public function test_index_renders_add_button_and_filter_toolbar(): void
    {
        $admin = $this->userWithRole('super-admin');

        $this->actingAs($admin)
            ->get(route('project-feedbacks.index'))
            ->assertOk()
            ->assertSee('data-bs-target="#addModal"', false)
            ->assertSee('id="feedbackFilterForm"', false)
            ->assertSee('Belum ada feedback');
    }

    public function test_show_returns_ticket_code_and_opd_for_detail_modal(): void
    {
        $opd = $this->opd('PUPR');
        $admin = $this->userWithRole('super-admin', $opd->id);
        $feedback = $this->feedbackFor($admin, ['opd_id' => $opd->id, 'created_at' => '2026-01-15 08:00:00']);

        $this->actingAs($admin)
            ->getJson(route('project-feedbacks.show', $feedback->id))
            ->assertOk()
            ->assertJsonPath('kode', $feedback->ticketCode())
            ->assertJsonPath('data.opd.singkatan', 'PUPR');
    }

    public function test_index_renders_redesigned_feedback_modals(): void
    {
        $admin = $this->userWithRole('super-admin');
        $this->feedbackFor($admin);

        $this->actingAs($admin)
            ->get(route('project-feedbacks.index'))
            ->assertOk()
            ->assertSee('id="show_loading"', false)
            ->assertSee('id="show_kode"', false)
            ->assertSee('id="show_gambar_file_wrap"', false)
            ->assertSee('id="response_ticket"', false)
            ->assertSee('pemberi feedback menerima email', false);
    }
}
