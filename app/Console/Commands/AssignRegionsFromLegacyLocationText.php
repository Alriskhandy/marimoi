<?php

namespace App\Console\Commands;

use App\Models\AdministrativeRegion;
use App\Models\DataSpatial;
use App\Models\DevelopmentProject;
use App\Models\SpatialLayerFeature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Isi region_id (spatial_layer_features) dan project_regions (development_projects) dari
 * teks lokasi bebas (dbf_attributes.LOKASI) yang tersisa dari import shapefile lama.
 *
 * Verifikasi data sebelum command ini ditulis (2026-09-27): dari 11.927 baris data_spatial,
 * hanya 125 yang punya pola "Kec. X, Kab./Kota Y" yang cukup jelas untuk diparse aman —
 * mayoritas LOKASI berisi nama tempat bebas tanpa pola ("Bobong", "Laut, Halmahera, ...")
 * yang TIDAK bisa dipetakan otomatis tanpa risiko salah. Command ini sengaja hanya
 * memproses baris berpola jelas, dan di dalam baris itu pun HANYA meng-assign bila
 * kandidat wilayah yang cocok persis satu (tidak ambigu) — kalau ragu, dibiarkan NULL.
 */
class AssignRegionsFromLegacyLocationText extends Command
{
    protected $signature = 'marimoi:assign-regions-from-location-text {--dry-run : Tampilkan hasil tanpa menyimpan}';

    protected $description = 'Isi region_id/project_regions dari teks LOKASI legacy (hanya baris berpola jelas, tanpa menebak yang ambigu)';

    private Collection $kabupatenList;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->kabupatenList = AdministrativeRegion::level('kabupaten_kota')->get();

        $rows = DataSpatial::query()
            ->select(['id', 'kategori_id', 'data_type', 'dbf_attributes'])
            ->whereNotNull('dbf_attributes')
            ->get()
            ->filter(fn ($row) => preg_match('/KEC\.?\s/i', (string) ($row->dbf_attributes['LOKASI'] ?? '')));

        $this->info("Baris dengan pola LOKASI jelas: {$rows->count()}");

        $stats = ['kecamatan_match' => 0, 'kabupaten_only_match' => 0, 'no_match' => 0, 'ambiguous' => 0];

        foreach ($rows as $row) {
            $lokasi = (string) $row->dbf_attributes['LOKASI'];
            $kabupaten = $this->matchKabupaten($lokasi);

            if ($kabupaten === 'ambiguous') {
                $stats['ambiguous']++;

                continue;
            }

            if (! $kabupaten) {
                $stats['no_match']++;

                continue;
            }

            $kecamatan = $this->matchKecamatan($lokasi, $kabupaten);
            $regionId = $kecamatan?->id ?? $kabupaten->id;
            $stats[$kecamatan ? 'kecamatan_match' : 'kabupaten_only_match']++;

            if ($dryRun) {
                $this->line("[{$row->id}] \"{$lokasi}\" -> ".($kecamatan?->name ?? $kabupaten->name));

                continue;
            }

            SpatialLayerFeature::where('legacy_data_spatial_id', $row->id)->update(['region_id' => $regionId]);

            $project = DevelopmentProject::where('legacy_data_spatial_id', $row->id)->first();
            if ($project) {
                $project->regions()->syncWithoutDetaching([$regionId]);
            }
        }

        $this->table(array_keys($stats), [array_values($stats)]);

        if ($dryRun) {
            $this->warn('Dry run — tidak ada perubahan disimpan.');
        }

        return self::SUCCESS;
    }

    /**
     * Cari kabupaten/kota yang cocok. Mengembalikan null bila tidak ketemu, atau string
     * 'ambiguous' bila lebih dari satu kandidat cocok (sengaja tidak menebak salah satu).
     */
    private function matchKabupaten(string $lokasi): AdministrativeRegion|string|null
    {
        if (! preg_match('/KAB(?:UPATEN)?\.?\s+([A-Z\s]+?)(?:,|$)/i', $lokasi, $m)
            && ! preg_match('/KOTA\s+([A-Z\s]+?)(?:,|$)/i', $lokasi, $m)) {
            return null;
        }

        $needle = $this->normalize($m[1]);

        $matches = $this->kabupatenList->filter(function (AdministrativeRegion $region) use ($needle) {
            $name = $this->normalize(str_ireplace(['Kabupaten ', 'Kota '], '', $region->name));

            return str_contains($needle, $name) || str_contains($name, $needle);
        });

        return match (true) {
            $matches->count() === 1 => $matches->first(),
            $matches->count() > 1 => 'ambiguous',
            default => null,
        };
    }

    private function matchKecamatan(string $lokasi, AdministrativeRegion $kabupaten): ?AdministrativeRegion
    {
        if (! preg_match('/KEC(?:AMATAN)?\.?\s+([A-Z\s]+?)(?:,|\s+KAB|\s+KOTA|$)/i', $lokasi, $m)) {
            return null;
        }

        $needle = $this->normalize($m[1]);

        $matches = $kabupaten->children()->level('kecamatan')->get()->filter(
            fn (AdministrativeRegion $kec) => str_contains($needle, $this->normalize($kec->name))
                || str_contains($this->normalize($kec->name), $needle)
        );

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', strtoupper($text)));
    }
}
