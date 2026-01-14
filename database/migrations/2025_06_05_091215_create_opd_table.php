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
        Schema::create('opd', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('singkatan', 20)->unique();
            $table->string('logo')->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            
            $table->index('singkatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opd');
    }
};
