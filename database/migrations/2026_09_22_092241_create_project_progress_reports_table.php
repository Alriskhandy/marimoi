<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_progress_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_spatial_id')->constrained('data_spatial')->restrictOnDelete();
            $table->foreignId('opd_id')->nullable()->constrained('opd')->nullOnDelete();
            $table->foreignId('kategori_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedSmallInteger('tahun_anggaran');
            $table->string('periode_laporan', 20);
            $table->decimal('pagu', 18, 2)->nullable();
            $table->decimal('realisasi_anggaran', 18, 2)->nullable();
            $table->decimal('progres_fisik_persen', 5, 2)->default(0);
            $table->string('status', 20)->default('belum_mulai');
            $table->text('catatan')->nullable();
            $table->foreignId('dilaporkan_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['data_spatial_id', 'tahun_anggaran', 'periode_laporan'],
                'project_progress_reports_unique_period'
            );
            $table->index(['opd_id', 'tahun_anggaran']);
            $table->index(['kategori_id', 'tahun_anggaran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_progress_reports');
    }
};
