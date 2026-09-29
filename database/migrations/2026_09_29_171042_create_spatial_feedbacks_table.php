<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bagian 1.5 docs/marimoi v2/04_implementation/12-implementasi-perbaikan-pemetaan.md.
     * Feedback umum untuk Layer ATAU Data Spasial — beda dari project_feedbacks yang
     * spesifik proyek strategis. CHECK constraint memastikan TEPAT SATU target terisi,
     * bukan cuma validasi form yang bisa dilewati jalur lain.
     */
    public function up(): void
    {
        Schema::create('spatial_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spatial_layer_id')->nullable()->constrained('spatial_layers')->cascadeOnDelete();
            $table->foreignId('spatial_layer_feature_id')->nullable()->constrained('spatial_layer_features')->cascadeOnDelete();
            $table->string('nama_pemberi');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('pesan');
            $table->string('status', 20)->default('baru');
            $table->text('response_admin')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        DB::statement('
            ALTER TABLE spatial_feedbacks
            ADD CONSTRAINT spatial_feedbacks_target_check
            CHECK (
                (spatial_layer_id IS NOT NULL AND spatial_layer_feature_id IS NULL)
                OR (spatial_layer_id IS NULL AND spatial_layer_feature_id IS NOT NULL)
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spatial_feedbacks');
    }
};
