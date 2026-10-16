<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `sumber_data`/`opd_penanggung_jawab_id`/`tanggal_data` sempat jadi kolom wajib
     * diisi manual tiap kali membuat Jenis Peta baru. Ternyata salah tempat — ketiganya
     * lebih cocok jadi definisi di katalog `metadata_definitions` (sama seperti
     * pagu/realisasi_anggaran/realisasi_fisik/status) yang DIPASANG OTOMATIS & WAJIB
     * ke setiap Jenis Peta (baru maupun lama), bukan diketik manual di form Jenis
     * Peta dan tidak bisa diubah lewat form itu lagi (lihat MapTypeController::
     * syncDynamicAttributes() yang selalu memaksa ulang 3 definisi ini).
     */
    public function up(): void
    {
        $now = now();

        $definitions = [
            ['kode' => 'sumber_data', 'label' => 'Sumber Data', 'data_type' => 'text', 'satuan' => null],
            ['kode' => 'opd_penanggung_jawab', 'label' => 'OPD Penanggung Jawab', 'data_type' => 'text', 'satuan' => null],
            ['kode' => 'tanggal_data', 'label' => 'Tahun/Tanggal Data', 'data_type' => 'date', 'satuan' => null],
        ];

        foreach ($definitions as $definition) {
            DB::table('metadata_definitions')->insertOrIgnore([
                'kode' => $definition['kode'],
                'label' => $definition['label'],
                'data_type' => $definition['data_type'],
                'satuan' => $definition['satuan'],
                'is_system' => true,
                'is_filterable' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $definitionIds = DB::table('metadata_definitions')
            ->whereIn('kode', array_column($definitions, 'kode'))
            ->pluck('id', 'kode');

        $mapTypeIds = DB::table('map_types')->pluck('id');

        foreach ($mapTypeIds as $urutan => $mapTypeId) {
            foreach (array_values($definitionIds->toArray()) as $i => $definitionId) {
                DB::table('map_type_dynamic_attributes')->insertOrIgnore([
                    'map_type_id' => $mapTypeId,
                    'metadata_definition_id' => $definitionId,
                    'is_wajib' => true,
                    'is_enabled' => true,
                    'is_active' => true,
                    'urutan' => $i,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Schema::table('map_types', function (Blueprint $table) {
            $table->dropForeign(['opd_penanggung_jawab_id']);
            $table->dropColumn(['sumber_data', 'opd_penanggung_jawab_id', 'tanggal_data']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('map_types', function (Blueprint $table) {
            $table->string('sumber_data')->nullable()->after('deskripsi');
            $table->foreignId('opd_penanggung_jawab_id')->nullable()->after('sumber_data')
                ->constrained('opd')->nullOnDelete();
            $table->date('tanggal_data')->nullable()->after('opd_penanggung_jawab_id');
        });

        DB::table('map_type_dynamic_attributes')
            ->whereIn('metadata_definition_id', function ($query) {
                $query->select('id')->from('metadata_definitions')
                    ->whereIn('kode', ['sumber_data', 'opd_penanggung_jawab', 'tanggal_data']);
            })
            ->delete();

        DB::table('metadata_definitions')
            ->whereIn('kode', ['sumber_data', 'opd_penanggung_jawab', 'tanggal_data'])
            ->delete();
    }
};
