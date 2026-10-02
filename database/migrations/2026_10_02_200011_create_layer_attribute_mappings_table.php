<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Skema v3 §5.9 — pemetaan kolom file sumber ke atribut standar, per impor.
 *
 * Deviasi dari dokumen (plan Keputusan #2): `attribute_definition_id` FK ke
 * `metadata_definitions` (katalog atribut global yang sudah ada & teruji),
 * BUKAN ke tabel `layer_attribute_definitions` yang sengaja TIDAK dibuat —
 * sistem atribut dinamis tetap di-scope per Jenis Peta (map_type), bukan
 * per-layer individual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layer_attribute_mappings', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('layer_import_id');
            $table->string('source_field_name', 255);
            $table->string('source_field_type', 50)->nullable();
            $table->foreignId('attribute_definition_id')->nullable()->constrained('metadata_definitions')->nullOnDelete();
            $table->boolean('is_ignored')->default(false);
            $table->jsonb('transform')->default('{}');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('layer_import_id')->references('id')->on('layer_imports')->cascadeOnDelete();
            $table->unique(['layer_import_id', 'source_field_name'], 'uq_layer_attr_map_source');
        });

        DB::statement('ALTER TABLE layer_attribute_mappings ADD CONSTRAINT ck_layer_attr_map_target CHECK (is_ignored OR attribute_definition_id IS NOT NULL)');
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX uq_layer_attr_map_target
                ON layer_attribute_mappings (layer_import_id, attribute_definition_id)
                WHERE attribute_definition_id IS NOT NULL
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('layer_attribute_mappings');
    }
};
