<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_progress_reports', function (Blueprint $table) {
            // 'manual' (default, input langsung admin) | 'inaproc' (nilai keuangan bersumber
            // sinkronisasi INAPROC, lihat docs/marimoi v2/03_plan/11-integrasi-inaproc.md —
            // kolom disiapkan sekarang, nilai 'inaproc' belum dipakai karena sinkronisasi
            // belum diimplementasikan).
            $table->string('sumber_data', 20)->default('manual')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('project_progress_reports', function (Blueprint $table) {
            $table->dropColumn('sumber_data');
        });
    }
};
