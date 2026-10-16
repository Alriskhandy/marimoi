<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bug fidelitas data ditemukan saat Fase 4 (plan mellow-weaving-eclipse):
 * `MigrateSchemaV3::migrateFeatures()` jalur utama membawa
 * `spatial_layer_features.attributes`/`metadata_dinamis` sebagai `properties`
 * — TIDAK pernah menyertakan `data_spatial.tahun` (kolom terstruktur
 * terpisah, bukan bagian dbf_attributes). Akibatnya filter tahun di peta
 * publik (FrontendController::getGeojsonByDataType) kehilangan data untuk
 * fitur yang sumbernya data_spatial.tahun terisi. Idempoten (hanya
 * menambah key 'tahun' bila belum ada & sumbernya terisi).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE spatial_features_v3 sf
            SET properties = sf.properties || jsonb_build_object('tahun', ds.tahun)
            FROM data_spatial ds
            WHERE ds.id = sf.legacy_data_spatial_id
              AND ds.tahun IS NOT NULL
              AND NOT jsonb_exists(sf.properties, 'tahun')
            SQL);
    }

    public function down(): void
    {
        // Tidak dibalik — menghapus key 'tahun' lagi tidak ada manfaatnya dan
        // berisiko membuang data yang mungkin sudah ditulis ulang sejak saat
        // migration ini jalan (lihat SpatialFeaturesV3Sync yang sejak Fase 4
        // ikut menulis key ini pada setiap create/update data_spatial baru).
    }
};
