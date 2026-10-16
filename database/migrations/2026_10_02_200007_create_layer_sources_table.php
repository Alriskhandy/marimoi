<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Skema v3 §5.6 — asal data layer (file unggahan atau layanan eksternal).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layer_sources', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('layer_id');
            $table->string('source_type', 30);
            $table->string('name', 150)->nullable();
            $table->text('url')->nullable();
            $table->string('service_layer_name', 200)->nullable();
            $table->string('format', 50)->nullable();
            $table->string('crs', 30)->nullable();
            $table->string('auth_type', 20)->default('none');
            $table->string('credential_ref', 200)->nullable();
            $table->jsonb('options')->default('{}');
            $table->boolean('is_primary')->default(false);
            $table->string('health_status', 20)->default('unknown');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->foreign('layer_id')->references('id')->on('layers_v3')->cascadeOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE layer_sources ADD CONSTRAINT ck_layer_sources_type CHECK (source_type IN
                ('file_upload','wms','wmts','wfs','xyz','arcgis_rest','geojson_url','vector_tile','cog_url','database'))
            SQL);
        DB::statement("ALTER TABLE layer_sources ADD CONSTRAINT ck_layer_sources_auth_type CHECK (auth_type IN ('none','api_key','basic','token'))");
        DB::statement("ALTER TABLE layer_sources ADD CONSTRAINT ck_layer_sources_health CHECK (health_status IN ('unknown','ok','error'))");
        DB::statement(<<<'SQL'
            ALTER TABLE layer_sources ADD CONSTRAINT ck_layer_sources_url CHECK (
                source_type IN ('file_upload','database') OR url IS NOT NULL)
            SQL);
        DB::statement('CREATE UNIQUE INDEX uq_layer_sources_primary ON layer_sources (layer_id) WHERE is_primary');
    }

    public function down(): void
    {
        Schema::dropIfExists('layer_sources');
    }
};
