<?php

namespace App\Console\Commands;

use App\Models\MetadataDefinition;
use Illuminate\Console\Command;

/**
 * Backfill `metadata_definitions` (docs/marimoi v2/03_plan/
 * 14-penyesuaian-database-jenis-peta.md Bagian 6 Tahap 3, Opsi B): seed 4
 * definisi siap-pakai bawaan (`MetadataDefinition::SYSTEM_DEFINITIONS`, dulu PHP
 * constant `MapTypeDynamicAttribute::PLACEHOLDER_ATTRIBUTES`) sebagai baris
 * `is_system=true` — sumber kebenaran tunggal di database, bukan lagi kode.
 *
 * Catatan implementasi (2026-09-30): saat command ini pertama dibuat, tabel
 * `map_type_dynamic_attributes` di lingkungan dev masih kosong (0 baris) —
 * migration Tahap 4 (`finalize_map_type_dynamic_attributes_pivot_table`) yang
 * menghapus kolom lama (`tipe`/`kode_atribut`/`label`/`satuan`) sudah langsung
 * dijalankan tanpa perlu tahap backfill-per-baris (tidak ada data existing untuk
 * dikonflikkan/di-dedup). Kalau di masa depan ada environment lain dengan data
 * lama di kolom yang sudah dihapus itu, backfill-nya harus dijalankan dari
 * snapshot database SEBELUM migration Tahap 4, bukan dari command ini.
 *
 * Idempoten: dijalankan berkali-kali tidak membuat duplikat (`firstOrCreate` by
 * `kode`) dan tidak mengubah baris yang sudah ada.
 */
class MigrateMetadataDefinitions extends Command
{
    protected $signature = 'marimoi:migrate-metadata-definitions {--dry-run}';

    protected $description = 'Seed metadata_definitions is_system dari MetadataDefinition::SYSTEM_DEFINITIONS';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $report = [];

        foreach (MetadataDefinition::SYSTEM_DEFINITIONS as $kode => $meta) {
            $existing = MetadataDefinition::where('kode', $kode)->first();
            $status = $existing ? 'sudah ada' : 'dibuat';

            $report[] = [$kode, $meta['label'], $meta['satuan'] ?? '-', $status];

            if ($dryRun || $existing) {
                continue;
            }

            MetadataDefinition::create([
                'kode' => $kode,
                'label' => $meta['label'],
                'satuan' => $meta['satuan'] ?? null,
                'data_type' => MetadataDefinition::TYPE_TEXT,
                'is_system' => true,
            ]);
        }

        $this->table(['Kode', 'Label', 'Satuan', 'Status'], $report);

        if ($dryRun) {
            $this->comment('--dry-run: tidak ada perubahan disimpan.');
        }

        return self::SUCCESS;
    }
}
