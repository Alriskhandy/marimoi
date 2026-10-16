<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Prasyarat skema v3 (docs/marimoi v2/db-schema-v3.md §5.0): `ltree` untuk
 * path hierarki category_nodes, `pg_trgm` untuk pencarian nama layer fuzzy.
 * postgis & gen_random_uuid() native sudah tersedia (PostgreSQL 17), tidak
 * perlu pgcrypto.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS ltree');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS pg_trgm');
        DB::statement('DROP EXTENSION IF EXISTS ltree');
    }
};
