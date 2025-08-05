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
        // 1. Tabel Roles (Independent table - no dependencies)
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 2. Tabel OPDs (Independent table - no dependencies)
        Schema::create('opds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('singkatan', 20);
            $table->string('logo')->nullable();
            $table->text('alamat')->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('singkatan');
        });

        // 3. Tabel Categories Aspirasi (Depends on: opds)
        Schema::create('categories_aspirasi', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
            $table->string('nama_usulan');
            $table->text('deskripsi')->nullable();
            $table->string('kode_kategori', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('opd_id');
            $table->index(['opd_id', 'is_active']);
            $table->index('kode_kategori');
        });

        // 4. Tabel Aspirasi (Depends on: categories_aspirasi, users)
        Schema::create('aspirasi', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('kategori_id')->nullable()->constrained('categories_aspirasi')->nullOnDelete();
            $table->string('nomor_tiket', 20)->unique()->nullable();
            $table->string('nama_pengirim');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->text('alamat');
            $table->enum('jenis_tanggapan', ['usulan', 'keluhan', 'kritik', 'saran'])->default('usulan');
            $table->text('isi_aspirasi');
            $table->json('lampiran')->nullable(); // Untuk menyimpan multiple files
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('tanggapan_admin')->nullable();
            $table->enum('status', ['pending', 'diproses', 'selesai', 'ditolak'])->default('pending');
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi', 'urgent'])->default('sedang');
            $table->timestamp('tanggal_respon')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('prioritas');
            $table->index(['kategori_id', 'status']);
            $table->index('nomor_tiket');
            $table->index('created_at');
        });




    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to avoid foreign key constraint issues
        Schema::dropIfExists('aspirasi');
        Schema::dropIfExists('categories_aspirasi');
        Schema::dropIfExists('opds');
        Schema::dropIfExists('roles');
    }
};