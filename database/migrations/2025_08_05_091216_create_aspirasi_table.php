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
        Schema::create('aspirasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_aspirasi_id')->constrained('kategori_aspirasi')->cascadeOnDelete();
            $table->string('nomor_tiket', 30)->unique();
            $table->string('nama_pengirim');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->text('alamat');
            $table->enum('jenis_aspirasi', ['usulan', 'keluhan', 'kritik', 'saran'])->default('usulan');
            $table->string('judul_aspirasi');
            $table->text('isi_aspirasi');
            $table->json('lampiran')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('tanggapan_admin')->nullable();
            $table->enum('status', ['pending', 'diproses', 'selesai', 'ditolak'])->default('pending');
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi', 'urgent'])->default('sedang');
            $table->timestamp('tanggal_respon')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('status');
            $table->index('prioritas');
            $table->index(['kategori_aspirasi_id', 'status']);
            $table->index('nomor_tiket');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aspirasi');
    }
};
