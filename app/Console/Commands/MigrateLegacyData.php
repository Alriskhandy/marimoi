<?php

namespace App\Console\Commands;

use App\Models\FeatureImage;
use App\Models\Layer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MigrateLegacyData extends Command
{
    protected $signature = 'app:migrate-legacy
                            {--dry-run  : Tampilkan ringkasan tanpa menyimpan perubahan}
                            {--fresh    : Kosongkan layers/features/feature_images sebelum migrasi}
                            {--chunk=500 : Jumlah record per batch}';

    protected $description = 'Migrasi data lama (categories & data_spatial) ke entitas baru (layers, features, feature_images)';

    /** @var array<int, int|string> old category_id => new layer_id */
    private array $categoryToLayerMap = [];

    /** @var array<string, int|string> "data_type[:sub_type]" => new layer_id */
    private array $defaultLayerMap = [];

    /** @var array<int, int> old data_spatial_id => new feature_id */
    private array $dataSpatialToFeatureMap = [];

    public function handle(): int
    {
        $isDryRun  = (bool) $this->option('dry-run');
        $isFresh   = (bool) $this->option('fresh');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $this->info($isDryRun
            ? '=== DRY RUN — tidak ada perubahan yang disimpan ==='
            : '=== Memulai migrasi data legacy ==='
        );

        if (!$this->validateSourceTables()) {
            return self::FAILURE;
        }

        if ($isFresh && !$isDryRun) {
            if (!$this->confirm('Opsi --fresh akan menghapus SEMUA data di layers, features, dan feature_images. Lanjutkan?')) {
                $this->warn('Migrasi dibatalkan.');
                return self::FAILURE;
            }
            $this->truncateDestinationTables();
        }

        DB::beginTransaction();
        try {
            $stats = [
                'layers_created'          => 0,
                'default_layers_created'  => 0,
                'features_migrated'       => 0,
                'features_skipped'        => 0,
                'images_migrated'         => 0,
            ];

            $this->newLine();
            $this->info('── Step 1: Migrasi categories → layers ──');
            $stats['layers_created'] = $this->migrateCategoriesToLayers($isDryRun);

            $this->newLine();
            $this->info('── Step 2: Buat default layers untuk data_spatial tanpa kategori ──');
            $stats['default_layers_created'] = $this->createDefaultLayers($isDryRun);

            $this->newLine();
            $this->info('── Step 3: Migrasi data_spatial → features ──');
            [$stats['features_migrated'], $stats['features_skipped']] = $this->migrateFeatures($chunkSize, $isDryRun);

            $this->newLine();
            $this->info('── Step 4: Migrasi gambar → feature_images ──');
            $stats['images_migrated'] = $this->migrateImages($chunkSize, $isDryRun);

            if ($isDryRun) {
                DB::rollBack();
                $this->newLine();
                $this->warn('DRY RUN: semua perubahan di-rollback.');
            } else {
                DB::commit();
            }

            $this->printSummary($stats);
            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('Migrasi gagal: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VALIDATION
    // ─────────────────────────────────────────────────────────────────────────

    private function validateSourceTables(): bool
    {
        foreach (['categories', 'data_spatial'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("Tabel '{$table}' tidak ditemukan di database.");
                return false;
            }
        }

        $catCount = DB::table('categories')->count();
        $dsCount  = DB::table('data_spatial')->count();
        $this->line("  Ditemukan {$catCount} categories dan {$dsCount} data_spatial records.");

        return true;
    }

    private function truncateDestinationTables(): void
    {
        $this->warn('Mengosongkan tabel tujuan...');
        DB::statement('TRUNCATE TABLE feature_images, features, layers RESTART IDENTITY CASCADE');
        $this->info('Tabel tujuan berhasil dikosongkan.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 1: CATEGORIES → LAYERS
    // ─────────────────────────────────────────────────────────────────────────

    private function migrateCategoriesToLayers(bool $isDryRun): int
    {
        $categories = DB::table('categories')->orderBy('id')->get();

        if ($categories->isEmpty()) {
            $this->line('  Tidak ada categories ditemukan.');
            return 0;
        }

        $geomTypePerCategory = $this->detectGeomTypePerCategory();
        $ordered = $this->sortCategoriesBFS($categories);

        $bar = $this->output->createProgressBar(count($ordered));
        $bar->start();

        $created = 0;
        foreach ($ordered as $cat) {
            $geomType = $geomTypePerCategory[$cat->id] ?? 'point';

            $style = array_filter([
                'color'         => $cat->warna       ?? null,
                'icon'          => $cat->icon         ?? null,
                'gambar'        => $cat->gambar        ?? null,
                'is_marker'     => (bool) ($cat->is_marker ?? false),
                'description'   => $cat->deskripsi    ?? null,
                'original_type' => $cat->type,
            ], fn($v) => $v !== null && $v !== '');

            $parentLayerId = $cat->parent_id
                ? ($this->categoryToLayerMap[$cat->parent_id] ?? null)
                : null;

            $layerData = [
                'name'      => $cat->nama ?? ucwords(str_replace('_', ' ', $cat->type)),
                'type'      => $geomType,
                'style'     => $style ?: null,
                'parent_id' => $isDryRun ? null : $parentLayerId,
                'is_active' => (bool) ($cat->is_active ?? true),
                'user_id'   => $cat->user_id,
            ];

            if (!$isDryRun) {
                $layer = Layer::create($layerData);
                $this->categoryToLayerMap[$cat->id] = $layer->id;
            } else {
                $this->categoryToLayerMap[$cat->id] = 'CAT-' . $cat->id;
            }

            $created++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("  ✓ {$created} categories → layers");
        return $created;
    }

    /**
     * Deteksi geometry type dominan per kategori dari kolom geom di data_spatial.
     * Menggunakan PostGIS ST_GeometryType().
     *
     * @return array<int, string>  kategori_id => 'point'|'line'|'polygon'
     */
    private function detectGeomTypePerCategory(): array
    {
        $rows = DB::select("
            SELECT
                kategori_id,
                CASE
                    WHEN UPPER(dominant_type) LIKE '%POINT%'   THEN 'point'
                    WHEN UPPER(dominant_type) LIKE '%LINE%'    THEN 'line'
                    WHEN UPPER(dominant_type) LIKE '%POLYGON%' THEN 'polygon'
                    ELSE 'point'
                END AS geom_type
            FROM (
                SELECT
                    kategori_id,
                    ST_GeometryType(geom) AS dominant_type,
                    COUNT(*) AS cnt
                FROM data_spatial
                WHERE kategori_id IS NOT NULL AND geom IS NOT NULL
                GROUP BY kategori_id, ST_GeometryType(geom)
                ORDER BY kategori_id, cnt DESC
            ) ranked
            WHERE (kategori_id, cnt) IN (
                SELECT kategori_id, MAX(cnt)
                FROM (
                    SELECT kategori_id, ST_GeometryType(geom) AS geom_type, COUNT(*) AS cnt
                    FROM data_spatial
                    WHERE kategori_id IS NOT NULL AND geom IS NOT NULL
                    GROUP BY kategori_id, ST_GeometryType(geom)
                ) sub
                GROUP BY kategori_id
            )
        ");

        $map = [];
        foreach ($rows as $row) {
            $map[$row->kategori_id] = $row->geom_type;
        }
        return $map;
    }

    /**
     * Urutkan categories secara BFS (root terlebih dahulu) agar parent_id bisa
     * di-resolve saat proses pembuatan layer anak.
     */
    private function sortCategoriesBFS(\Illuminate\Support\Collection $categories): array
    {
        $indexed    = $categories->keyBy('id')->toArray();
        $childrenOf = [];
        $roots      = [];

        foreach ($indexed as $id => $cat) {
            if (is_null($cat->parent_id)) {
                $roots[] = $id;
            } else {
                $childrenOf[$cat->parent_id][] = $id;
            }
        }

        $ordered = [];
        $queue   = $roots;
        while (!empty($queue)) {
            $id = array_shift($queue);
            if (!isset($indexed[$id])) {
                continue;
            }
            $ordered[] = $indexed[$id];
            foreach ($childrenOf[$id] ?? [] as $childId) {
                $queue[] = $childId;
            }
        }

        // Append categories yang parent_id-nya tidak ditemukan (orphan)
        $processedIds = array_column($ordered, 'id');
        foreach ($indexed as $id => $cat) {
            if (!in_array($id, $processedIds, true)) {
                $this->warn("  Kategori ID {$id} memiliki parent_id={$cat->parent_id} yang tidak ditemukan, diproses tanpa parent.");
                $ordered[] = $cat;
            }
        }

        return $ordered;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 2: DEFAULT LAYERS UNTUK ORPHAN DATA_SPATIAL
    // ─────────────────────────────────────────────────────────────────────────

    private function createDefaultLayers(bool $isDryRun): int
    {
        $orphanGroups = DB::table('data_spatial')
            ->whereNull('kategori_id')
            ->select('data_type', 'sub_type')
            ->distinct()
            ->get();

        if ($orphanGroups->isEmpty()) {
            $this->line('  Semua data_spatial memiliki kategori, tidak perlu default layer.');
            return 0;
        }

        $labelMap = [
            'tematik'           => 'Tematik',
            'usulan_musrenbang' => 'Usulan Musrenbang',
            'pokir_dprd'        => 'Pokir DPRD',
            'proyek_strategis'  => 'Proyek Strategis',
        ];

        $created = 0;
        foreach ($orphanGroups as $group) {
            $key  = $group->data_type . ($group->sub_type ? ':' . $group->sub_type : '');
            if (isset($this->defaultLayerMap[$key])) {
                continue;
            }

            $name = $labelMap[$group->data_type] ?? ucwords(str_replace('_', ' ', $group->data_type));
            if ($group->sub_type) {
                $name .= ' (' . strtoupper($group->sub_type) . ')';
            }

            $geomType = $this->detectGeomTypeForDataType($group->data_type, $group->sub_type);

            if (!$isDryRun) {
                $layer = Layer::create([
                    'name'      => $name,
                    'type'      => $geomType,
                    'is_active' => true,
                    'user_id'   => 1,
                ]);
                $this->defaultLayerMap[$key] = $layer->id;
            } else {
                $this->defaultLayerMap[$key] = 'DEFAULT-' . $key;
            }

            $this->line("  + Layer default: \"{$name}\" (type={$geomType})");
            $created++;
        }

        $this->line("  ✓ {$created} default layers dibuat.");
        return $created;
    }

    private function detectGeomTypeForDataType(string $dataType, ?string $subType): string
    {
        $bindings = [$dataType];
        $subTypeSql = $subType
            ? 'AND sub_type = ?'
            : 'AND sub_type IS NULL';
        if ($subType) {
            $bindings[] = $subType;
        }

        $row = DB::selectOne("
            SELECT
                CASE
                    WHEN UPPER(ST_GeometryType(geom)) LIKE '%POINT%'   THEN 'point'
                    WHEN UPPER(ST_GeometryType(geom)) LIKE '%LINE%'    THEN 'line'
                    WHEN UPPER(ST_GeometryType(geom)) LIKE '%POLYGON%' THEN 'polygon'
                    ELSE 'point'
                END AS geom_type,
                COUNT(*) AS cnt
            FROM data_spatial
            WHERE data_type = ? {$subTypeSql}
              AND geom IS NOT NULL
            GROUP BY ST_GeometryType(geom)
            ORDER BY cnt DESC
            LIMIT 1
        ", $bindings);

        return $row?->geom_type ?? 'point';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 3: DATA_SPATIAL → FEATURES
    // ─────────────────────────────────────────────────────────────────────────

    private function migrateFeatures(int $chunkSize, bool $isDryRun): array
    {
        $total = DB::table('data_spatial')->count();
        if ($total === 0) {
            $this->line('  Tidak ada data_spatial ditemukan.');
            return [0, 0];
        }

        $migrated = 0;
        $skipped  = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::table('data_spatial')
            ->orderBy('id')
            ->chunk($chunkSize, function ($records) use (&$migrated, &$skipped, $isDryRun, $bar) {
                foreach ($records as $record) {
                    $layerId = $this->resolveLayerId($record);

                    if ($layerId === null) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    $properties = $this->buildProperties($record);

                    if (!$isDryRun) {
                        // Gunakan INSERT...SELECT...RETURNING agar geom ikut tercopy via SQL
                        // UUID baru di-generate oleh PostgreSQL; UUID lama disimpan di properties
                        $result = DB::selectOne("
                            INSERT INTO features (uuid, layer_id, user_id, properties, year, views, geom, created_at, updated_at)
                            SELECT
                                gen_random_uuid(),
                                :layer_id,
                                user_id,
                                :properties::jsonb,
                                tahun,
                                views,
                                geom,
                                created_at,
                                updated_at
                            FROM data_spatial
                            WHERE id = :id
                            RETURNING id
                        ", [
                            'layer_id'   => $layerId,
                            'properties' => json_encode($properties),
                            'id'         => $record->id,
                        ]);

                        if ($result) {
                            $this->dataSpatialToFeatureMap[$record->id] = $result->id;
                        }
                    }

                    $migrated++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->line("  ✓ {$migrated} features dimigrasikan, {$skipped} dilewati (tidak ada layer).");
        return [$migrated, $skipped];
    }

    private function resolveLayerId(object $record): int|string|null
    {
        // Prioritas 1: kategori_id yang sudah dipetakan ke layer
        if ($record->kategori_id !== null && isset($this->categoryToLayerMap[$record->kategori_id])) {
            return $this->categoryToLayerMap[$record->kategori_id];
        }

        // Prioritas 2: default layer berdasarkan data_type + sub_type
        $key = $record->data_type . ($record->sub_type ? ':' . $record->sub_type : '');
        if (isset($this->defaultLayerMap[$key])) {
            return $this->defaultLayerMap[$key];
        }

        // Prioritas 3: default layer berdasarkan data_type saja (tanpa sub_type)
        if ($record->sub_type && isset($this->defaultLayerMap[$record->data_type])) {
            return $this->defaultLayerMap[$record->data_type];
        }

        return null;
    }

    private function buildProperties(object $record): array
    {
        $properties = [];

        // Salin semua dbf_attributes sebagai base
        if (!empty($record->dbf_attributes)) {
            $decoded = is_string($record->dbf_attributes)
                ? json_decode($record->dbf_attributes, true)
                : (array) $record->dbf_attributes;
            $properties = array_merge($properties, $decoded ?? []);
        }

        // Tambah field metadata
        if (!empty($record->deskripsi)) {
            $properties['deskripsi'] = $record->deskripsi;
        }
        if (!empty($record->data_type)) {
            $properties['data_type'] = $record->data_type;
        }
        if (!empty($record->sub_type)) {
            $properties['sub_type'] = $record->sub_type;
        }

        // Simpan UUID lama sebagai referensi
        if (!empty($record->uuid)) {
            $properties['legacy_uuid'] = $record->uuid;
        }

        return $properties;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 4: GAMBAR → FEATURE_IMAGES
    // ─────────────────────────────────────────────────────────────────────────

    private function migrateImages(int $chunkSize, bool $isDryRun): int
    {
        $total = DB::table('data_spatial')
            ->whereNotNull('gambar')
            ->where('gambar', '!=', '')
            ->count();

        if ($total === 0) {
            $this->line('  Tidak ada gambar untuk dimigrasikan.');
            return 0;
        }

        $migrated = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::table('data_spatial')
            ->whereNotNull('gambar')
            ->where('gambar', '!=', '')
            ->orderBy('id')
            ->chunk($chunkSize, function ($records) use (&$migrated, $isDryRun, $bar) {
                $inserts = [];

                foreach ($records as $record) {
                    $featureId = $this->dataSpatialToFeatureMap[$record->id] ?? null;

                    // Fallback: cari feature berdasarkan legacy_uuid di properties
                    if (!$featureId && !$isDryRun && !empty($record->uuid)) {
                        $feature = DB::table('features')
                            ->whereRaw("properties->>'legacy_uuid' = ?", [$record->uuid])
                            ->value('id');
                        $featureId = $feature;
                    }

                    if (!$featureId) {
                        $bar->advance();
                        continue;
                    }

                    if (!$isDryRun) {
                        $inserts[] = [
                            'feature_id' => $featureId,
                            'file_path'  => $record->gambar,
                            'caption'    => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    $migrated++;
                    $bar->advance();
                }

                if (!empty($inserts)) {
                    FeatureImage::insert($inserts);
                }
            });

        $bar->finish();
        $this->newLine();
        $this->line("  ✓ {$migrated} feature_images dimigrasikan.");
        return $migrated;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SUMMARY
    // ─────────────────────────────────────────────────────────────────────────

    private function printSummary(array $stats): void
    {
        $this->newLine();
        $this->info('=== RINGKASAN MIGRASI ===');
        $this->table(
            ['Entitas', 'Jumlah'],
            [
                ['Layers dari categories',              $stats['layers_created']],
                ['Default layers (tanpa kategori)',      $stats['default_layers_created']],
                ['Features dimigrasikan',               $stats['features_migrated']],
                ['Features dilewati (layer tidak ada)', $stats['features_skipped']],
                ['Feature images dimigrasikan',         $stats['images_migrated']],
            ]
        );
    }
}
