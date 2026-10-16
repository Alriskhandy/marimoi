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
        Schema::create('shared_maps', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('layers');
            $table->json('viewport')->nullable();
            $table->string('data_type')->default('tematik');
            $table->string('sub_type')->nullable();
            $table->integer('year')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_maps');
    }
};
