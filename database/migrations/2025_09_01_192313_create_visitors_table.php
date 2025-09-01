<?php
// database/migrations/xxxx_create_visitors_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->string('ip');
            $table->text('user_agent')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('page_visited');
            $table->timestamps();

            $table->index(['ip', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};