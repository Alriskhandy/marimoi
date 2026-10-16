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
     * Katalog global definisi metadata (docs/marimoi v2/03_plan/
     * 14-penyesuaian-database-jenis-peta.md Bagian 6 Tahap 1, Opsi B) — reusable
     * lintas Jenis Peta, menggantikan PHP constant `PLACEHOLDER_ATTRIBUTES` dan
     * definisi yang sebelumnya menyatu di `map_type_dynamic_attributes`.
     *
     * 4 baris `is_system=true` di-seed langsung di sini (pola sama seperti
     * `create_map_types_table` yang meng-insert 5 baris awalnya) — bukan lewat
     * command `marimoi:migrate-metadata-definitions`, supaya setiap environment
     * baru (termasuk `RefreshDatabase` di test) otomatis punya katalog dasar tanpa
     * langkah manual tambahan. Command tetap ada untuk memastikan idempoten di
     * environment yang sudah berjalan sebelum migration ini (lihat command-nya).
     */
    public function up(): void
    {
        Schema::create('metadata_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('label');
            $table->text('deskripsi')->nullable();
            $table->string('data_type', 20)->default('text'); // text|integer|decimal|currency|select|date
            $table->string('satuan')->nullable();
            $table->jsonb('opsi')->nullable(); // untuk data_type=select
            $table->jsonb('validasi')->nullable(); // {"min":0,"max":100} dst
            $table->boolean('is_system')->default(false); // true = placeholder bawaan, tidak bisa dihapus
            $table->boolean('is_filterable')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('metadata_definitions')->insert([
            ['kode' => 'pagu', 'label' => 'Pagu', 'satuan' => 'Rp', 'data_type' => 'text', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'realisasi_anggaran', 'label' => 'Realisasi Anggaran', 'satuan' => 'Rp', 'data_type' => 'text', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'realisasi_fisik', 'label' => 'Realisasi Fisik', 'satuan' => '%', 'data_type' => 'text', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'status', 'label' => 'Status', 'satuan' => null, 'data_type' => 'text', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metadata_definitions');
    }
};
