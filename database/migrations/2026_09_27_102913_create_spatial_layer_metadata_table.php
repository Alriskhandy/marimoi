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
        Schema::create('spatial_layer_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spatial_layer_id')->unique()->constrained('spatial_layers')->cascadeOnDelete();
            $table->text('abstract')->nullable();
            $table->string('source_name')->nullable();
            $table->text('source_url')->nullable();
            $table->string('license')->nullable();
            $table->text('attribution')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->date('data_reference_date')->nullable();
            $table->smallInteger('data_reference_year')->nullable();
            $table->string('update_frequency', 50)->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->text('lineage')->nullable();
            $table->text('positional_accuracy')->nullable();
            $table->text('attribute_accuracy')->nullable();
            $table->text('completeness')->nullable();
            $table->text('limitations')->nullable();
            $table->string('language_code', 10)->default('id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Backfill 1 baris metadata per layer dari sumber_data/tanggal_data milik baris
        // data_spatial PERTAMA tiap kategori (keputusan sementara — lihat Prasyarat Prioritas 0
        // di 09-implementasi-penuh-database-v2.md: metadata saat ini disimpan per-feature,
        // bukan per-layer, jadi ini pilihan salah satu representative row, bukan agregasi).
        DB::statement('
            INSERT INTO spatial_layer_metadata (spatial_layer_id, source_name, data_reference_year, created_at, updated_at)
            SELECT DISTINCT ON (sl.id)
                sl.id,
                ds.sumber_data,
                EXTRACT(YEAR FROM ds.tanggal_data)::int,
                now(),
                now()
            FROM spatial_layers sl
            JOIN data_spatial ds ON ds.kategori_id = sl.legacy_category_id
            WHERE ds.sumber_data IS NOT NULL
            ORDER BY sl.id, ds.created_at ASC
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spatial_layer_metadata');
    }
};
