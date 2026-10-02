<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi untuk SpatialFeedbackController (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 3.3). Fixture Layer ditulis
 * lewat DB::table('spatial_layers') langsung — kolom
 * `spatial_feedbacks.spatial_layer_id` punya FK sungguhan ke tabel v2
 * tersebut (bigint), bukan ke layers_v3 (uuid) yang sejak Fase 3 jadi
 * representasi Eloquent `SpatialLayer` (lihat juga SpatialFeedbackTest).
 */
class SpatialFeedbackControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        foreach (['spatial-feedbacks.view', 'spatial-feedbacks.respond', 'spatial-feedbacks.delete'] as $perm) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function layerId(): int
    {
        return DB::table('spatial_layers_legacy_v2')->insertGetId([
            'public_id' => (string) Str::uuid(),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
            'title' => 'Layer Uji',
            'visibility' => 'private',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_public_can_submit_feedback_for_a_layer_without_auth(): void
    {
        $layerId = $this->layerId();

        $this->postJson(route('spatial-feedbacks.store'), [
            'spatial_layer_id' => $layerId,
            'nama_pemberi' => 'Warga Uji',
            'pesan' => 'Contoh pesan',
        ])->assertCreated();

        $this->assertDatabaseHas('spatial_feedbacks', ['spatial_layer_id' => $layerId, 'nama_pemberi' => 'Warga Uji']);
    }

    public function test_public_submission_without_any_target_is_rejected(): void
    {
        $this->postJson(route('spatial-feedbacks.store'), [
            'nama_pemberi' => 'Warga Uji',
            'pesan' => 'Contoh pesan',
        ])->assertStatus(422);
    }

    public function test_public_submission_with_both_targets_is_rejected(): void
    {
        $layerId = $this->layerId();

        $this->postJson(route('spatial-feedbacks.store'), [
            'spatial_layer_id' => $layerId,
            'spatial_layer_feature_id' => 999,
            'nama_pemberi' => 'Warga Uji',
            'pesan' => 'Contoh pesan',
        ])->assertStatus(422);
    }

    public function test_admin_can_respond_to_feedback(): void
    {
        $admin = $this->admin();
        $feedback = SpatialFeedback::create([
            'spatial_layer_id' => $this->layerId(), 'nama_pemberi' => 'Warga Uji', 'pesan' => 'Contoh pesan',
        ]);

        $this->actingAs($admin)->put(route('spatial-feedbacks.respond', $feedback), [
            'response_admin' => 'Terima kasih atas masukannya.',
            'status' => 'ditanggapi',
        ])->assertRedirect(route('spatial-feedbacks.index'));

        $feedback->refresh();
        $this->assertSame('ditanggapi', $feedback->status);
        $this->assertNotNull($feedback->responded_at);
    }

    public function test_user_without_permission_cannot_view_feedback_list(): void
    {
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('spatial-feedbacks.index'))->assertForbidden();
    }

    /**
     * Regresi: index perlu benar-benar dirender (bukan cuma dites via aksi POST/PUT
     * lain) supaya kesalahan kompilasi Blade pada halaman ini ketahuan — pola sama
     * seperti bug @json() yang sempat lolos di map-types/_form.blade.php.
     */
    public function test_admin_can_view_feedback_index_with_data(): void
    {
        $admin = $this->admin();
        SpatialFeedback::create(['spatial_layer_id' => $this->layerId(), 'nama_pemberi' => 'Warga Uji', 'pesan' => 'Contoh pesan']);

        $this->actingAs($admin)->get(route('spatial-feedbacks.index'))->assertOk()->assertSee('Warga Uji');
    }
}
