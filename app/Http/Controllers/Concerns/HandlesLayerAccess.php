<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SpatialLayer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Helper akses OPD yang sebelumnya di-copy-paste private di
 * SpatialLayerController/LayerStyleController/LayerImportController —
 * diekstrak di sini supaya LayerWizardController (controller keempat yang
 * membutuhkan logika yang sama persis) tidak jadi salinan kelima. Controller
 * lama SENGAJA tidak dimigrasikan ke trait ini di sesi yang sama (diff tetap
 * fokus); boleh dirapikan nanti karena semuanya sudah tercakup test.
 */
trait HandlesLayerAccess
{
    /**
     * Tolak akses admin-opd ke Layer milik OPD lain atau tanpa OPD (R19).
     * Peran lain (super-admin/admin-bappeda) selalu lolos.
     */
    private function authorizeOpdAccess(SpatialLayer $spatialLayer): void
    {
        if ($this->isAdminOpd() && $spatialLayer->opd_id !== $this->currentOpdId()) {
            abort(403, 'Anda tidak memiliki akses ke Layer milik OPD lain.');
        }
    }

    private function isAdminOpd(): bool
    {
        return Auth::user()?->role?->slug === 'admin-opd';
    }

    /**
     * super-admin/admin-bappeda boleh memilih/memindahkan opd_id bebas (R21);
     * admin-opd tidak (opd_id-nya selalu dari dirinya sendiri, lihat R20).
     */
    private function canAssignOpd(): bool
    {
        return ! $this->isAdminOpd();
    }

    private function currentOpdId(): ?int
    {
        return Auth::user()?->opd_id;
    }

    /**
     * Tolak kombinasi category_id/category_node_id yang tidak konsisten —
     * dipakai setiap kali form menerima keduanya (R7-ish, subkategori harus
     * anak dari kategori yang dipilih, bukan dari kategori lain).
     */
    private function assertCategoryNodeBelongsToCategory(?string $categoryNodeId, string $categoryId): void
    {
        if (empty($categoryNodeId)) {
            return;
        }

        $belongsToCategory = DB::table('category_nodes')
            ->where('id', $categoryNodeId)
            ->where('category_id', $categoryId)
            ->exists();

        if (! $belongsToCategory) {
            throw ValidationException::withMessages([
                'category_node_id' => 'Subkategori tidak sesuai dengan Kategori yang dipilih.',
            ]);
        }
    }
}
