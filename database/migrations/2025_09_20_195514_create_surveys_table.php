<?php
// database/migrations/2024_01_01_000003_create_surveys_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('publication_download_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('organization')->nullable();
            $table->string('position')->nullable();
            $table->enum('survey_type', ['download', 'general'])->default('general'); // Tipe survey
            $table->integer('rating'); // 1-5
            $table->text('feedback')->nullable();
            $table->text('suggestions')->nullable(); // Saran untuk website/publikasi
            $table->json('additional_data')->nullable(); // untuk data survey tambahan
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};