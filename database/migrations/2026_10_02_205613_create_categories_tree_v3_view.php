<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * View gabungan categories_v3 (root) + category_nodes (child/grandchild)
 * dengan nama kolom yang sama seperti `categories` v2 lama — supaya model
 * Eloquent `Category` (lihat app/Models/Category.php) & CategoryController
 * yang sudah ada tidak perlu ditulis ulang total untuk hirarki flat
 * `parent_id` 3 level (plan mellow-weaving-eclipse, Fase 3 lanjutan).
 *
 * Read-only (UNION ALL) — create/update/delete pada model `Category` di-override
 * untuk menulis langsung ke categories_v3/category_nodes berdasarkan `parent_id`
 * yang dikirim, lalu me-refresh dari view ini. `source_table` dipakai model
 * untuk tahu kemana harus menulis saat update/delete baris existing.
 */
return new class extends Migration
{
    public function up(): void
    {
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
    }
};
