<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Skema v3 §5.10 — simbolisasi layer. FK komposit layers_v3.default_style_id
 * ditambahkan di sini (setelah tabel ini ada), sesuai dokumen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layer_styles', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('layer_id');
            $table->string('name', 100);
            $table->string('style_type', 20);
            $table->string('renderer', 20)->default('maplibre');
            $table->string('classification_field', 63)->nullable();
            $table->jsonb('definition');
            $table->jsonb('legend')->default('[]');
            $table->text('sld')->nullable();
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('layer_id')->references('id')->on('layers_v3')->cascadeOnDelete();
            $table->unique(['layer_id', 'name'], 'uq_layer_styles_name');
            $table->unique(['id', 'layer_id'], 'uq_layer_styles_id_layer');
        });

        DB::statement("ALTER TABLE layer_styles ADD CONSTRAINT ck_layer_styles_type CHECK (style_type IN ('simple','categorized','graduated','heatmap','raster','external'))");
        DB::statement("ALTER TABLE layer_styles ADD CONSTRAINT ck_layer_styles_renderer CHECK (renderer IN ('maplibre','leaflet','generic'))");
        DB::statement(<<<'SQL'
            ALTER TABLE layer_styles ADD CONSTRAINT ck_layer_styles_class CHECK (
                style_type NOT IN ('categorized','graduated') OR classification_field IS NOT NULL)
            SQL);
        DB::statement('CREATE UNIQUE INDEX uq_layer_styles_default ON layer_styles (layer_id) WHERE is_default');

        DB::statement(<<<'SQL'
            ALTER TABLE layers_v3 ADD CONSTRAINT fk_layers_v3_default_style
                FOREIGN KEY (default_style_id, id)
                REFERENCES layer_styles (id, layer_id)
                DEFERRABLE INITIALLY DEFERRED
            SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE layers_v3 DROP CONSTRAINT IF EXISTS fk_layers_v3_default_style');
        Schema::dropIfExists('layer_styles');
    }
};
