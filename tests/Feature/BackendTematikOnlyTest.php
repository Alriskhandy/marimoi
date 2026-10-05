<?php

namespace Tests\Feature;

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
 * ada penggantinya).
 *
 * `test_category_index_rejects_legacy_types()` ikut dihapus 2026-10-06:
 * kolom `categories_v3.type`/`category_nodes` terkait dibuang (lihat migration
 * drop_display_and_type_columns_from_categories_v3_and_category_nodes) —
 * `CategoryController::index()` tidak lagi membaca/memvalidasi query string
 * `type` sama sekali, jadi perilaku "redirect untuk tipe legacy" yang diuji
 * di sini bukan lagi bug kalau hilang, memang sudah tidak ada.
 */
class BackendTematikOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_backend_routes_are_removed(): void
    {
        foreach (['psd.index', 'psn.index', 'pokir-dprd.index', 'usulan-musrenbang.index'] as $routeName) {
            $this->assertFalse(Route::has($routeName), $routeName);
        }
    }
}
