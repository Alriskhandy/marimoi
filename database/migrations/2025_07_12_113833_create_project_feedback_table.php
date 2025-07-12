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
        Schema::create('project_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->string('nama_proyek');
            $table->string('nama_pemberi_aspirasi');
            $table->string('kabupaten_kota');
            $table->string('kecamatan')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('laporan_gambar')->nullable();
            $table->text('tanggapan');
            $table->enum('jenis_tanggapan', ['keluhan', 'saran', 'apresiasi', 'pertanyaan'])->default('saran');
            $table->enum('status', ['pending', 'ditinjau', 'ditindaklanjuti', 'selesai'])->default('pending');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('response_admin')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            
            // Indexes khusus untuk Maluku Utara
            $table->index(['kabupaten_kota', 'status']);
            $table->index(['nama_proyek', 'jenis_tanggapan']);
            $table->index(['created_at']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_feedbacks');
    }
};