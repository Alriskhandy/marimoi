<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fase H (spec-admin-manajemen-peta.md §5.1 butir 2, "move node") — cascading
 * update category_id/depth/path ke seluruh keturunan saat sebuah node
 * dipindah HARUS menulis induk dulu baru turunannya (atau sebaliknya), dan
 * selama proses itu `fk_category_nodes_parent` (composite FK self-referensial
 * (parent_id, category_id) -> (id, category_id)) akan sempat tidak konsisten
 * di tengah transaksi — baris anak belum sempat diperbarui padahal baris
 * induknya sudah category_id baru (atau sebaliknya).
 *
 * Fix yang sama persis sudah dipakai `fk_layers_v3_default_style` (lihat
 * create_layer_styles_table) untuk masalah serupa: jadikan FK ini
 * DEFERRABLE INITIALLY DEFERRED, supaya Postgres baru memeriksa constraint
 * di akhir transaksi (COMMIT), bukan per statement.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE category_nodes DROP CONSTRAINT fk_category_nodes_parent');
        DB::statement(<<<'SQL'
            ALTER TABLE category_nodes ADD CONSTRAINT fk_category_nodes_parent
                FOREIGN KEY (parent_id, category_id)
                REFERENCES category_nodes (id, category_id) ON DELETE CASCADE
                DEFERRABLE INITIALLY DEFERRED
            SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE category_nodes DROP CONSTRAINT fk_category_nodes_parent');
        DB::statement(<<<'SQL'
            ALTER TABLE category_nodes ADD CONSTRAINT fk_category_nodes_parent
                FOREIGN KEY (parent_id, category_id)
                REFERENCES category_nodes (id, category_id) ON DELETE CASCADE
            SQL);
    }
};
