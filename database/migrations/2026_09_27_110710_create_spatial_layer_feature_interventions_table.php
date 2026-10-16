<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('spatial_layer_feature_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id_eksisting')->constrained('spatial_layer_features')->cascadeOnDelete();
            $table->foreignId('feature_id_intervensi')->constrained('spatial_layer_features')->cascadeOnDelete();
            $table->string('jenis_hubungan', 50)->default('terkait');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['feature_id_eksisting', 'feature_id_intervensi'], 'slf_interventions_unique_pair');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spatial_layer_feature_interventions');
    }
};
