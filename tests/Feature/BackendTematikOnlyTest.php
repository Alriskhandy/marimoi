<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Sisa dari file ini setelah Fase I/D13 (plan mellow-weaving-eclipse) —
 * retirement modul "Data Spasial" lama. 6 test yang dihapus dari file ini
 * (menu sidebar lama, switcher tabel/peta, export gambar peta KML/KMZ)
 * sudah DISETUJUI untuk dihapus karena menguji perilaku `DataSpatialController`
 * yang sudah di-retire (rute `data-spatial.*` sekarang murni redirect ke
 * `spatial-layers.*`, lihat routes/backend.php) — termasuk "Export Gambar
 * Peta" yang sengaja TIDAK diport ke modul baru (disetujui eksplisit, tidak
 * ada penggantinya). Dua test yang tersisa di bawah TIDAK terkait modul itu
 * sama sekali (satu soal rute lama non-tematik, satu soal CategoryController),
 * jadi tetap relevan dan dipertahankan.
 */
class BackendTematikOnlyTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_legacy_backend_routes_are_removed(): void
    {
        foreach (['psd.index', 'psn.index', 'pokir-dprd.index', 'usulan-musrenbang.index'] as $routeName) {
            $this->assertFalse(Route::has($routeName), $routeName);
        }
    }

    public function test_category_index_rejects_legacy_types(): void
    {
        $this->actingAs($this->superAdmin())
            ->from(route('dashboard'))
            ->get(route('categories.index', ['type' => 'psd']))
            ->assertRedirect(route('dashboard'));
    }
}
