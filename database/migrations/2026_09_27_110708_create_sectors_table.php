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
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Daftar sektor awal — contoh, bukan daftar final. Konfirmasi daftar lengkap
        // ke Bappeda saat sektor benar-benar dipakai untuk klasifikasi layer/proyek.
        DB::table('sectors')->insert(collect([
            'pupr' => 'PUPR',
            'kesehatan' => 'Kesehatan',
            'pendidikan' => 'Pendidikan',
            'ekonomi' => 'Ekonomi',
            'lingkungan' => 'Lingkungan Hidup',
        ])->map(fn ($name, $code) => [
            'code' => $code,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ])->values()->all());

        Schema::table('spatial_layers', function (Blueprint $table) {
            $table->foreign('sector_id')->references('id')->on('sectors')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spatial_layers', function (Blueprint $table) {
            $table->dropForeign(['sector_id']);
        });

        Schema::dropIfExists('sectors');
    }
};
