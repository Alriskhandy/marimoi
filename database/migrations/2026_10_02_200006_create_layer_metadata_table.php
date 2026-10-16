<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Skema v3 §5.5 — metadata deskriptif 1:1 dengan layers_v3. Nama tabel
 * final langsung dipakai (tidak collision dengan tabel lama
 * spatial_layer_metadata).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layer_metadata', function (Blueprint $table) {
            $table->uuid('layer_id')->primary();
            $table->string('title', 300)->nullable();
            $table->text('abstract')->nullable();
            $table->text('purpose')->nullable();
            $table->string('topic_category', 60)->nullable();
            $table->string('producer_organization', 200)->nullable();
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->smallInteger('data_year')->nullable();
            $table->date('reference_date')->nullable();
            $table->string('date_type', 20)->nullable();
            $table->string('update_frequency', 20)->nullable();
            $table->integer('scale_denominator')->nullable();
            $table->string('positional_accuracy', 100)->nullable();
            $table->integer('source_srid')->nullable();
            $table->string('administrative_area', 200)->nullable();
            $table->text('lineage')->nullable();
            $table->string('license', 100)->nullable();
            $table->text('use_constraints')->nullable();
            $table->jsonb('extra')->default('{}');
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('layer_id')->references('id')->on('layers_v3')->cascadeOnDelete();
        });

        DB::statement("ALTER TABLE layer_metadata ADD COLUMN keywords text[] NOT NULL DEFAULT '{}'");
        DB::statement('ALTER TABLE layer_metadata ADD CONSTRAINT ck_layer_metadata_data_year CHECK (data_year BETWEEN 1900 AND 2100)');
        DB::statement("ALTER TABLE layer_metadata ADD CONSTRAINT ck_layer_metadata_date_type CHECK (date_type IN ('creation','publication','revision'))");
        DB::statement(<<<'SQL'
            ALTER TABLE layer_metadata ADD CONSTRAINT ck_layer_metadata_update_frequency CHECK (update_frequency IN
                ('once','annually','semiannually','quarterly','monthly','irregular'))
            SQL);
        DB::statement('ALTER TABLE layer_metadata ADD CONSTRAINT ck_layer_metadata_scale CHECK (scale_denominator > 0)');
        DB::statement('CREATE INDEX ix_layer_metadata_keywords ON layer_metadata USING gin (keywords)');
    }

    public function down(): void
    {
        Schema::dropIfExists('layer_metadata');
    }
};
