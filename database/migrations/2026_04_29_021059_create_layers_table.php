<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Aktifkan PostGIS
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        /**
         * =========================
         * LAYERS (Nested Support)
         * =========================
         */
        Schema::create('layers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            
            // tipe geometry
            $table->enum('type', ['point', 'line', 'polygon']);

            // styling default layer
            $table->jsonb('style')->nullable();

            // nested (parent-child)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('layers')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // index untuk nested & filtering
            $table->index('parent_id');
            $table->index('type');
            $table->index('is_active');
        });

        /**
         * =========================
         * FEATURES (Spatial Data)
         * =========================
         */
        Schema::create('features', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique()->nullable();

            // relasi ke layer
            $table->foreignId('layer_id')
                ->constrained('layers')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // atribut fleksibel
            $table->jsonb('properties')->nullable();

            // metadata opsional
            $table->integer('year')->nullable();
            $table->integer('views')->default(0);

            $table->timestamps();

            // index penting
            $table->index('layer_id');
            $table->index('user_id');
            $table->index('year');

            // JSONB index
            $table->index('properties', null, 'gin');
        });

        Schema::create('feature_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('caption')->nullable();
            $table->timestamps();
        });

        /**
         * =========================
         * GEOMETRY COLUMN
         * =========================
         */
        DB::statement('ALTER TABLE features ADD COLUMN geom GEOMETRY');

        /**
         * =========================
         * SPATIAL INDEX (WAJIB)
         * =========================
         */
        DB::statement('CREATE INDEX features_geom_idx ON features USING GIST (geom)');

        /**
         * =========================
         * JSONB OPTIMIZATION
         * =========================
         */
        DB::statement('CREATE INDEX features_properties_gin ON features USING GIN (properties jsonb_path_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_images');
        Schema::dropIfExists('features');
        Schema::dropIfExists('layers');
    }
};
