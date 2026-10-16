<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_progress_report_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_progress_report_id')
                ->constrained('project_progress_reports')
                ->cascadeOnDelete();
            // Snapshot nilai (pagu, realisasi_anggaran, progres_fisik_persen, status, catatan)
            // sebelum ditimpa oleh pembaruan ini — bukan kolom per field supaya generik
            // terhadap field yang boleh diperbarui tanpa migration tambahan di kemudian hari.
            $table->json('data_sebelumnya');
            $table->foreignId('diperbarui_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('project_progress_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_progress_report_revisions');
    }
};
