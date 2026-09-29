<?php

namespace App\Console\Commands;

use App\Models\MapType;
use App\Models\SpatialLayer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Granularisasi Jenis (docs/marimoi v2/04_implementation/12-implementasi-perbaikan-pemetaan.md
 * Bagian 1.6, menjalankan Keputusan #1): map_types saat ini cuma 5 baris kasar.
 * Command ini membuat satu Jenis baru per Layer AKAR (parent_id null), lalu seluruh
 * keturunannya ikut Jenis itu — bukan 211 Jenis terpisah untuk tiap baris.
 *
 * Idempoten: dijalankan dua kali tidak membuat Jenis duplikat (slug disertakan id
 * root, unik permanen) dan tidak mengubah assignment yang sudah benar.
 */
class GranularizeMapTypes extends Command
{
    protected $signature = 'marimoi:granularize-map-types {--dry-run}';

    protected $description = 'Buat Jenis (map_types) granular per Layer akar, pindahkan Layer & keturunannya ke Jenis baru';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $roots = SpatialLayer::whereNull('parent_id')->orderBy('id')->get();

        if ($roots->isEmpty()) {
            $this->warn('Tidak ada spatial_layers dengan parent_id null — tidak ada yang bisa digranularisasi.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($roots as $root) {
            $slug = Str::slug($root->name).'-'.$root->id;
            $descendantIds = $this->collectDescendantIds($root->id);
            $affectedCount = 1 + count($descendantIds);

            $rows[] = [$root->id, $root->name, $slug, $affectedCount];

            if ($dryRun) {
                continue;
            }

            $mapType = MapType::firstOrCreate(
                ['slug' => $slug],
                ['nama' => $root->name, 'is_active' => true]
            );

            SpatialLayer::whereIn('id', array_merge([$root->id], $descendantIds))
                ->update(['map_type_id' => $mapType->id]);
        }

        $this->table(['Layer Akar (id)', 'Nama', 'Slug Jenis Baru', 'Jumlah Layer Ikut'], $rows);

        if ($dryRun) {
            $this->comment('--dry-run: tidak ada perubahan disimpan. Review daftar di atas sebelum menjalankan tanpa --dry-run.');

            return self::SUCCESS;
        }

        $deactivated = MapType::whereIn('slug', ['tematik', 'usulan_musrenbang', 'pokir_dprd', 'psd', 'psn'])
            ->whereDoesntHave('spatialLayers')
            ->update(['is_active' => false]);

        $this->info("Jenis lama yang dinonaktifkan (tidak ada Layer lagi menunjuk ke situ): {$deactivated}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, int>
     */
    private function collectDescendantIds(int $parentId): array
    {
        $ids = [];
        $queue = SpatialLayer::where('parent_id', $parentId)->pluck('id')->all();

        while (! empty($queue)) {
            $id = array_shift($queue);
            $ids[] = $id;
            $children = SpatialLayer::where('parent_id', $id)->pluck('id')->all();
            array_push($queue, ...$children);
        }

        return $ids;
    }
}
