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
     * Kolom lama (data_spatial_id, nama_proyek, kabupaten_kota) TIDAK dihapus — tetap
     * jadi snapshot historis sesuai db-schema-v2.md §5.5. development_project_id
     * ditambahkan berdampingan sebagai relasi baru yang lebih terstruktur.
     */
    public function up(): void
    {
        Schema::table('project_progress_reports', function (Blueprint $table) {
            $table->foreignId('development_project_id')->nullable()->after('data_spatial_id')
                ->constrained('development_projects')->nullOnDelete();
        });

        Schema::table('project_feedbacks', function (Blueprint $table) {
            $table->foreignId('development_project_id')->nullable()->after('data_spatial_id')
                ->constrained('development_projects')->nullOnDelete();
        });

        DB::statement('
            UPDATE project_progress_reports ppr
            SET development_project_id = dp.id
            FROM development_projects dp
            WHERE dp.legacy_data_spatial_id = ppr.data_spatial_id
        ');

        DB::statement('
            UPDATE project_feedbacks pf
            SET development_project_id = dp.id
            FROM development_projects dp
            WHERE dp.legacy_data_spatial_id = pf.data_spatial_id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_feedbacks', function (Blueprint $table) {
            $table->dropForeign(['development_project_id']);
            $table->dropColumn('development_project_id');
        });

        Schema::table('project_progress_reports', function (Blueprint $table) {
            $table->dropForeign(['development_project_id']);
            $table->dropColumn('development_project_id');
        });
    }
};
