<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Skema v3 §5.7 — riwayat impor file ke spatial_features_v3. Tanpa
 * layer_versions (lihat dokumen §1.5), tabel ini satu-satunya riwayat
 * perubahan data layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layer_imports', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('layer_id');
            $table->uuid('layer_source_id')->nullable();
            $table->string('original_filename', 255);
            $table->text('storage_path');
            $table->string('file_format', 20);
            $table->bigInteger('file_size_bytes')->nullable();
            $table->char('checksum_sha256', 64)->nullable();
            $table->integer('source_srid')->nullable();
            $table->integer('target_srid')->default(4326);
            $table->string('encoding', 30)->default('UTF-8');
            $table->string('import_mode', 20)->default('replace');
            $table->string('status', 20)->default('pending');
            $table->jsonb('detected_fields')->default('[]');
            $table->integer('total_features')->nullable();
            $table->integer('imported_features')->default(0);
            $table->integer('failed_features')->default(0);
            $table->text('error_message')->nullable();
            $table->jsonb('log')->default('{}');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('layer_id')->references('id')->on('layers_v3')->cascadeOnDelete();
            $table->foreign('layer_source_id')->references('id')->on('layer_sources')->nullOnDelete();
            $table->unique(['id', 'layer_id'], 'uq_layer_imports_id_layer');
        });

        DB::statement("ALTER TABLE layer_imports ADD CONSTRAINT ck_layer_imports_format CHECK (file_format IN ('shp_zip','geojson','kml','kmz','gpkg','csv'))");
        DB::statement('ALTER TABLE layer_imports ADD CONSTRAINT ck_layer_imports_size CHECK (file_size_bytes >= 0)');
        DB::statement("ALTER TABLE layer_imports ADD CONSTRAINT ck_layer_imports_mode CHECK (import_mode IN ('replace','append'))");
        DB::statement(<<<'SQL'
            ALTER TABLE layer_imports ADD CONSTRAINT ck_layer_imports_status CHECK (status IN
                ('pending','uploaded','mapping','processing','completed','failed','cancelled'))
            SQL);

        DB::statement('CREATE INDEX ix_layer_imports_layer ON layer_imports (layer_id, created_at DESC)');
        DB::statement(<<<'SQL'
            CREATE INDEX ix_layer_imports_status ON layer_imports (status)
                WHERE status IN ('pending','uploaded','mapping','processing')
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('layer_imports');
    }
};
