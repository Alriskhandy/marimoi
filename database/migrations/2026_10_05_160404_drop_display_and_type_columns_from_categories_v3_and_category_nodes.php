<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simplifikasi kategori (2026-10-06, atas permintaan user): `categories_v3`/
 * `category_nodes` tidak lagi menyimpan tipe/gaya tampil sendiri — gaya
 * tampil (ikon/warna/marker) sudah pindah penuh ke `layer_styles` per-Layer
 * sejak skema v3, dan pengelompokan jenis sudah tersedia lewat
 * `layers.map_type_id` -> `map_types`, jadi `categories_v3.type` (dan
 * turunannya di view `categories_tree_v3`) jadi duplikat. Kolom yang
 * dihapus:
 * - `categories_v3`: code, type, icon, color, is_active, gambar, is_marker.
 * - `category_nodes`: is_active, icon, color, gambar, is_marker (tabel ini
 *   tidak pernah punya code/type sendiri — view mengambilnya dari
 *   categories_v3 root lewat join).
 *
 * View harus di-DROP+CREATE ulang (bukan ALTER) karena Postgres tidak bisa
 * mengubah daftar kolom view existing — lihat catatan yang sama di migration
 * create_categories_tree_v3_view.php.
 *
 * Filter yang sebelumnya bergantung pada `categories_v3.type` (endpoint
 * publik /peta-tematik di FrontendController, dan beberapa tempat lain)
 * SENGAJA belum diganti ke sumber lain di migration ini — itu perubahan
 * logika aplikasi yang ditangani terpisah di kode PHP, bukan bagian dari
 * migration skema.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS categories_tree_v3');

        DB::statement('DROP INDEX IF EXISTS uq_categories_v3_code');
        DB::statement('DROP INDEX IF EXISTS ix_categories_v3_type');

        Schema::table('categories_v3', function (Blueprint $table) {
            $table->dropColumn(['code', 'type', 'icon', 'color', 'is_active', 'gambar', 'is_marker']);
        });

        Schema::table('category_nodes', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'icon', 'color', 'gambar', 'is_marker']);
        });

        DB::statement(<<<'SQL'
            CREATE VIEW categories_tree_v3 AS
            SELECT
                c.id,
                c.name AS nama,
                c.created_by AS user_id,
                c.description AS deskripsi,
                NULL::uuid AS parent_id,
                0 AS depth,
                c.sort_order,
                c.created_at,
                c.updated_at,
                'categories_v3' AS source_table
            FROM categories_v3 c
            WHERE c.deleted_at IS NULL

            UNION ALL

            SELECT
                cn.id,
                cn.name AS nama,
                cn.created_by AS user_id,
                cn.description AS deskripsi,
                COALESCE(cn.parent_id, cn.category_id) AS parent_id,
                cn.depth,
                cn.sort_order,
                cn.created_at,
                cn.updated_at,
                'category_nodes' AS source_table
            FROM category_nodes cn
            JOIN categories_v3 c ON c.id = cn.category_id
            WHERE cn.deleted_at IS NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS categories_tree_v3');

        Schema::table('categories_v3', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('id');
            $table->string('type', 60)->nullable()->after('slug');
            $table->string('icon', 100)->nullable();
            $table->string('color', 9)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('gambar')->nullable();
            $table->boolean('is_marker')->default(false);
        });

        DB::statement('CREATE UNIQUE INDEX uq_categories_v3_code ON categories_v3 (code) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX ix_categories_v3_type ON categories_v3 (type) WHERE deleted_at IS NULL');

        Schema::table('category_nodes', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->string('icon', 100)->nullable();
            $table->string('color', 9)->nullable();
            $table->string('gambar')->nullable();
            $table->boolean('is_marker')->default(false);
        });

        DB::statement(<<<'SQL'
            CREATE VIEW categories_tree_v3 AS
            SELECT
                c.id,
                c.type,
                c.name AS nama,
                c.color AS warna,
                c.icon,
                c.is_marker,
                c.created_by AS user_id,
                c.description AS deskripsi,
                NULL::uuid AS parent_id,
                c.is_active,
                c.gambar,
                0 AS depth,
                c.sort_order,
                c.created_at,
                c.updated_at,
                'categories_v3' AS source_table
            FROM categories_v3 c
            WHERE c.deleted_at IS NULL

            UNION ALL

            SELECT
                cn.id,
                c.type,
                cn.name AS nama,
                cn.color AS warna,
                cn.icon,
                cn.is_marker,
                cn.created_by AS user_id,
                cn.description AS deskripsi,
                COALESCE(cn.parent_id, cn.category_id) AS parent_id,
                cn.is_active,
                cn.gambar,
                cn.depth,
                cn.sort_order,
                cn.created_at,
                cn.updated_at,
                'category_nodes' AS source_table
            FROM category_nodes cn
            JOIN categories_v3 c ON c.id = cn.category_id
            WHERE cn.deleted_at IS NULL
        SQL);
    }
};
