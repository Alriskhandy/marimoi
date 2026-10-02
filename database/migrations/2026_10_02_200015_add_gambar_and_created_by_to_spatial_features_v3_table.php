<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deviasi dari dokumen (ditemukan saat Fase 3, rewrite SpatialLayerFeatureController):
 * - `gambar`: foto per-fitur adalah fitur nyata & teruji yang dibangun di admin UI
 *   (upload saat create/edit Data Spasial, ditampilkan di tabel & modal detail).
 *   Tidak ada kolom setara di dokumen v3 — dipertahankan sebagai kolom tambahan,
 *   sama kategorinya dengan `region_id`/`map_type_id` yang sudah didokumentasikan
 *   sebelumnya.
 * - `created_by`: audit pembuat fitur, ada di tabel lama (spatial_layer_features)
 *   tapi terlewat di skema v3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spatial_features_v3', function (Blueprint $table) {
            $table->string('gambar')->nullable()->after('label');
            $table->foreignId('created_by')->nullable()->after('region_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('spatial_features_v3', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('gambar');
        });
    }
};
