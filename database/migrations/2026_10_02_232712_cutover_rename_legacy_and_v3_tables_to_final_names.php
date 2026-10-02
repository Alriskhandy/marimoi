<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 6 (plan mellow-weaving-eclipse) — cutover final. RENAME TABLE adalah
 * operasi metadata-only di PostgreSQL (instan, dalam transaksi DDL) — FK,
 * index, trigger, dan view yang bergantung pada tabel-tabel ini otomatis
 * ikut menunjuk nama baru, tidak perlu dibuat ulang.
 *
 * `categories_v3`/`category_nodes` SENGAJA TIDAK di-rename ke `categories` —
 * berbeda dari asumsi awal rencana (satu tabel flat), implementasi final
 * Category (lihat app/Models/Category.php, Fase 3 lanjutan) adalah VIEW
 * `categories_tree_v3` di atas DUA tabel ini sekaligus, karena hirarki 3
 * level v2 dipecah jadi root (categories_v3) + turunan (category_nodes).
 * Tidak ada tabel tunggal "categories" v3 yang bisa menggantikan nama
 * `categories` secara langsung — konsumen (Category model, CategoryController)
 * sudah mengakses lewat nama final (categories_tree_v3), bukan nama tabel
 * mentah, jadi tidak ada manfaat fungsional merename kedua tabel ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('categories', 'categories_legacy_v1');
        Schema::rename('data_spatial', 'data_spatial_legacy_v1');
        Schema::rename('spatial_layers', 'spatial_layers_legacy_v2');
        Schema::rename('spatial_layer_features', 'spatial_layer_features_legacy_v2');
        Schema::rename('spatial_layer_metadata', 'spatial_layer_metadata_legacy_v2');

        Schema::rename('layers_v3', 'layers');
        Schema::rename('spatial_features_v3', 'spatial_features');
    }

    public function down(): void
    {
        Schema::rename('spatial_features', 'spatial_features_v3');
        Schema::rename('layers', 'layers_v3');

        Schema::rename('spatial_layer_metadata_legacy_v2', 'spatial_layer_metadata');
        Schema::rename('spatial_layer_features_legacy_v2', 'spatial_layer_features');
        Schema::rename('spatial_layers_legacy_v2', 'spatial_layers');
        Schema::rename('data_spatial_legacy_v1', 'data_spatial');
        Schema::rename('categories_legacy_v1', 'categories');
    }
};
