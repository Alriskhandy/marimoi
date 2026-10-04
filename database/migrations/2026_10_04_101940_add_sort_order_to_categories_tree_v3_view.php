<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fase H (spec-admin-manajemen-peta.md §5.1 butir 3) — `categories_v3`/
 * `category_nodes` sudah punya kolom `sort_order` sejak skema v3 awal, tapi
 * view `categories_tree_v3` (lihat migration create_categories_tree_v3_view)
 * belum pernah mengeksposnya, jadi model `Category` tidak bisa membaca/
 * menuliskannya. View harus di-DROP+CREATE ulang (bukan ALTER) karena
 * Postgres tidak bisa menambah kolom di tengah definisi view existing.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS categories_tree_v3');

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

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS categories_tree_v3');

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
};
