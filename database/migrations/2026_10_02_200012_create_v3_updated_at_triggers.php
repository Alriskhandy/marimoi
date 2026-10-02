<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Skema v3 §5.12 — trigger updated_at untuk semua tabel baru. Fungsi
 * set_updated_at() dibuat di sini (belum ada migration lain yang
 * mendefinisikannya).
 */
return new class extends Migration
{
    private const TABLES = [
        'categories_v3',
        'category_nodes',
        'layers_v3',
        'layer_metadata',
        'layer_sources',
        'layer_styles',
        'spatial_features_v3',
    ];

    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION set_updated_at() RETURNS trigger AS $$
            BEGIN
              NEW.updated_at := now();
              RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            SQL);

        foreach (self::TABLES as $table) {
            DB::statement("CREATE TRIGGER trg_{$table}_updated_at BEFORE UPDATE ON {$table} FOR EACH ROW EXECUTE FUNCTION set_updated_at()");
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            DB::statement("DROP TRIGGER IF EXISTS trg_{$table}_updated_at ON {$table}");
        }

        DB::unprepared('DROP FUNCTION IF EXISTS set_updated_at()');
    }
};
