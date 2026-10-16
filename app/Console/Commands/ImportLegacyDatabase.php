<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\MapDataVersion;
use Database\Seeders\PermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Impor isi database MARIMOI versi lama (koneksi `legacy`, lihat
 * config/database.php) ke database versi baru yang baru saja di-`migrate:fresh`.
 *
 * 1. Tabel yang strukturnya sama disalin apa adanya dengan ID asli (opd,
 *    roles, users, aspirasi, publikasi, visitors, dst.), termasuk tabel
 *    kompatibilitas v1 `categories` dan `data_spatial`.
 * 2. Backfill yang di migrate:fresh berjalan atas tabel kosong diulang:
 *    model_has_roles (Spatie), permission, development_projects.
 * 3. Skema V3 dibangun dari kategori & data spasial lama: root kategori ->
 *    `categories_v3`, turunannya -> `category_nodes`, setiap kategori yang
 *    punya data -> satu Layer `published` (+ style default & metadata), dan
 *    setiap baris data_spatial -> satu `spatial_features`.
 *
 * Semua langkah berjalan dalam satu transaksi: gagal di tengah = tidak ada
 * yang tersimpan. Tabel `layers`/`features` versi lama (modul WebGIS lama)
 * sengaja tidak ikut — tidak ada lagi kode yang membacanya.
 */
class ImportLegacyDatabase extends Command
{
    protected $signature = 'marimoi:import-legacy
        {--connection=legacy : Nama koneksi database versi lama}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Impor data MARIMOI versi lama ke database versi baru (V3)';

    /**
     * Tabel yang disalin apa adanya (nama & ID sama di kedua database),
     * berurutan sesuai dependensi FK.
     *
     * @var list<string>
     */
    private const COPIED_TABLES = [
        'opd',
        'roles',
        'users',
        'kategori_aspirasi',
        'aspirasi',
        'publications',
        'publication_downloads',
        'visitors',
        'api_tokens',
        'authentication_logs',
        'categories',
        'data_spatial',
        'project_feedbacks',
    ];

    /**
     * Tabel tujuan yang harus kosong sebelum impor dimulai.
     *
     * @var list<string>
     */
    private const MUST_BE_EMPTY = ['users', 'opd', 'categories', 'data_spatial', 'layers', 'spatial_features'];

    private const CHUNK_SIZE = 500;

    /** Satu geometri lama bisa >10 MB (EWKB), jadi dibaca sedikit-sedikit. */
    private const GEOMETRY_CHUNK_SIZE = 10;

    private Connection $legacy;

    /** @var array<string, int> */
    private array $report = [];

    /** @var array<string, true> */
    private array $usedSlugs = [];

    public function handle(): int
    {
        $this->legacy = DB::connection($this->option('connection'));

        if (! $this->targetIsEmpty()) {
            return self::FAILURE;
        }

        if (! $this->legacyHasTable('categories') || ! $this->legacyHasTable('data_spatial')) {
            $this->error('Database lama tidak punya tabel `categories`/`data_spatial`. Periksa koneksi `'.$this->option('connection').'`.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Impor data dari database lama ke "'.DB::connection()->getDatabaseName().'"?', true)) {
            return self::FAILURE;
        }

        DB::transaction(function () {
            $this->copyTables();
            $this->ensureReservedRoles();
            $this->syncSpatieRoles();
            $this->seedPermissions();
            $this->backfillDevelopmentProjects();
            $this->buildCategoryTree();
            $this->buildLayers();
            $this->copySpatialFeatures();
            $this->finalizeLayers();
        });

        MapDataVersion::forget();
        Artisan::call('cache:clear');

        $this->table(['Tabel', 'Baris'], collect($this->report)->map(fn (int $count, string $table) => [$table, number_format($count)])->values());
        $this->info('Impor selesai.');

        return self::SUCCESS;
    }

    private function targetIsEmpty(): bool
    {
        $filled = collect(self::MUST_BE_EMPTY)->filter(fn (string $table) => DB::table($table)->exists());

        if ($filled->isEmpty()) {
            return true;
        }

        $this->error('Database tujuan belum kosong ('.$filled->implode(', ').'). Jalankan `php artisan migrate:fresh` dulu.');

        return false;
    }

    private function copyTables(): void
    {
        // migrate:fresh sudah membuat role "Pimpinan" (id 1) — id itu milik
        // Super Admin di database lama, jadi role bawaan dibuat ulang setelah
        // roles lama tersalin (ensureReservedRoles).
        DB::table('model_has_roles')->delete();
        DB::table('role_has_permissions')->delete();
        DB::table('roles')->delete();

        foreach (self::COPIED_TABLES as $table) {
            if (! $this->legacyHasTable($table)) {
                $this->warn("Tabel lama `{$table}` tidak ada, dilewati.");

                continue;
            }

            $this->report[$table] = match ($table) {
                'categories' => $this->copyCategories(),
                'roles' => $this->copyTable($table, ['guard_name' => 'web']),
                default => $this->copyTable($table),
            };

            $this->resetSequence($table);
        }
    }

    /**
     * @param  array<string, mixed>  $defaults  Nilai untuk kolom yang hanya ada di tabel tujuan.
     */
    private function copyTable(string $table, array $defaults = []): int
    {
        $columns = array_values(array_intersect($this->legacyColumns($table), $this->targetColumns($table)));
        $geometryColumns = array_values(array_intersect($columns, $this->legacyGeometryColumns($table)));
        $selects = array_map(
            fn (string $column) => in_array($column, $geometryColumns, true)
                ? DB::raw("encode(ST_AsEWKB({$this->legacy->getQueryGrammar()->wrap($column)}), 'hex') as {$column}")
                : $column,
            $columns
        );
        $copied = 0;
        $seenIds = [];
        $duplicateRecords = [];

        $chunkSize = $geometryColumns === [] ? self::CHUNK_SIZE : self::GEOMETRY_CHUNK_SIZE;

        // ctid sebagai urutan kedua: beberapa tabel lama tidak punya primary
        // key (mis. visitors), jadi id saja tidak menjamin paging yang stabil.
        $this->legacy->table($table)->select($selects)->orderBy('id')->orderByRaw('ctid')
            ->chunk($chunkSize, function ($rows) use ($table, $geometryColumns, $defaults, &$copied, &$seenIds, &$duplicateRecords) {
                $records = [];

                foreach ($rows as $row) {
                    $record = (array) $row + $defaults;

                    if (isset($seenIds[$record['id']])) {
                        $duplicateRecords[] = $record;

                        continue;
                    }

                    $seenIds[$record['id']] = true;
                    $records[] = $record;
                }

                if ($records === []) {
                    return;
                }

                if ($geometryColumns === []) {
                    DB::table($table)->insert($records);
                } else {
                    foreach ($records as $record) {
                        $this->insertWithGeometry($table, $record, $geometryColumns);
                    }
                }

                $copied += count($records);
            });

        // Baris ber-id ganda tetap disimpan, dengan id baru dari sequence.
        if ($duplicateRecords !== []) {
            $this->resetSequence($table);

            foreach ($duplicateRecords as $record) {
                $oldId = $record['id'];
                unset($record['id']);
                $newId = $geometryColumns === []
                    ? DB::table($table)->insertGetId($record)
                    : $this->insertWithGeometry($table, $record, $geometryColumns);
                $this->warn("{$table}: id {$oldId} ganda, baris kedua disimpan sebagai id {$newId}.");
            }

            $copied += count($duplicateRecords);
        }

        return $copied;
    }

    /**
     * Geometri (EWKB hex dari koneksi lama) dikirim sebagai parameter terikat,
     * bukan ditempel ke teks SQL, supaya geometri besar tidak menggandakan
     * pemakaian memori.
     *
     * @param  array<string, mixed>  $record
     * @param  list<string>  $geometryColumns
     */
    private function insertWithGeometry(string $table, array $record, array $geometryColumns): int
    {
        $grammar = DB::connection()->getQueryGrammar();
        $columns = array_keys($record);
        $placeholders = array_map(
            fn (string $column) => in_array($column, $geometryColumns, true) ? "ST_GeomFromEWKB(decode(?, 'hex'))" : '?',
            $columns
        );

        $row = DB::selectOne(
            'INSERT INTO '.$grammar->wrapTable($table).' ('.$grammar->columnize($columns).') VALUES ('.implode(', ', $placeholders).') RETURNING id',
            array_values($record)
        );

        return (int) $row->id;
    }

    /**
     * Kategori saling merujuk lewat parent_id, dan induk bisa ber-ID lebih
     * besar dari anaknya — parent_id diisi setelah semua baris masuk.
     */
    private function copyCategories(): int
    {
        $copied = $this->copyTable('categories', ['parent_id' => null]);

        $this->legacy->table('categories')->whereNotNull('parent_id')->orderBy('id')
            ->each(fn ($row) => DB::table('categories')->where('id', $row->id)->update(['parent_id' => $row->parent_id]));

        return $copied;
    }

    /**
     * Role yang dipakai langsung oleh kode (RoleController::RESERVED_SLUGS +
     * pimpinan) tapi belum ada di database lama.
     */
    private function ensureReservedRoles(): void
    {
        $reserved = [
            'super-admin' => ['Super Admin', 'Administrator dengan akses penuh ke sistem'],
            'admin-bappeda' => ['Admin Bappeda', 'Administrator dengan akses tertentu ke sistem'],
            'admin-opd' => ['Admin OPD', 'Administrator untuk OPD tertentu'],
            'pimpinan' => ['Pimpinan', 'Pimpinan daerah/Bappeda: ringkasan eksekutif dashboard'],
            'user' => ['User', 'Pengguna publik'],
        ];

        foreach ($reserved as $slug => [$name, $description]) {
            if (DB::table('roles')->where('slug', $slug)->exists()) {
                continue;
            }

            DB::table('roles')->insert([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'is_active' => true,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->report['roles'] = DB::table('roles')->count();
    }

    /**
     * User::saved() yang biasanya menjaga model_has_roles tidak terpicu oleh
     * insert massal.
     */
    private function syncSpatieRoles(): void
    {
        DB::statement(
            'INSERT INTO model_has_roles (role_id, model_type, model_id) SELECT role_id, ?, id FROM users WHERE role_id IS NOT NULL',
            [(new User)->getMorphClass()]
        );

        $this->report['model_has_roles'] = DB::table('model_has_roles')->count();
    }

    private function seedPermissions(): void
    {
        Artisan::call('db:seed', ['--class' => PermissionSeeder::class, '--force' => true]);

        $this->report['permissions'] = DB::table('permissions')->count();
    }

    /**
     * Sama dengan backfill di migration create_development_projects_table
     * dan add_development_project_id_to_project_progress_reports_and_feedbacks.
     */
    private function backfillDevelopmentProjects(): void
    {
        $budgetPattern = '^\s*Rp\.?\s*[0-9]{1,3}(\.[0-9]{3})*(,[0-9]+)?\s*$';

        DB::statement("
            INSERT INTO development_projects
                (public_id, project_code, name, owner_opd_id, fiscal_year, status,
                 budget_amount, needs_review, legacy_data_spatial_id, created_at, updated_at)
            SELECT
                gen_random_uuid(),
                'LEGACY-' || ds.id,
                COALESCE(
                    NULLIF(ds.dbf_attributes->>'PAKET', ''),
                    NULLIF(ds.deskripsi, 'Data tanpa nama'),
                    'Proyek Tanpa Nama #' || ds.id
                ),
                ds.opd_pengelola_id,
                COALESCE(ds.tahun, EXTRACT(YEAR FROM now())::int),
                'berjalan',
                CASE
                    WHEN ds.dbf_attributes->>'ANGGARAN' ~ :pattern1
                    THEN replace(
                        replace(regexp_replace(trim(ds.dbf_attributes->>'ANGGARAN'), '^Rp\\.?\\s*', ''), '.', ''),
                        ',', '.'
                    )::numeric
                    ELSE NULL
                END,
                ds.opd_pengelola_id IS NULL
                    OR (ds.dbf_attributes->>'ANGGARAN' IS NOT NULL AND ds.dbf_attributes->>'ANGGARAN' !~ :pattern2),
                ds.id,
                now(),
                now()
            FROM data_spatial ds
            WHERE ds.data_type = 'proyek_strategis'
        ", ['pattern1' => $budgetPattern, 'pattern2' => $budgetPattern]);

        DB::statement('
            UPDATE project_feedbacks pf
            SET development_project_id = dp.id
            FROM development_projects dp
            WHERE dp.legacy_data_spatial_id = pf.data_spatial_id
        ');

        $this->report['development_projects'] = DB::table('development_projects')->count();
    }

    /**
     * Root kategori lama -> categories_v3; setiap turunannya -> category_nodes
     * (depth 1 = anak langsung root, path ltree mengikuti Category::create()).
     */
    private function buildCategoryTree(): void
    {
        $categories = DB::table('categories')->orderBy('id')->get();
        $childrenByParent = $categories->groupBy(fn ($category) => $category->parent_id ?? 0);
        $now = now();

        foreach ($childrenByParent->get(0, collect())->values() as $index => $root) {
            $rootId = (string) Str::uuid();

            DB::table('categories_v3')->insert([
                'id' => $rootId,
                'name' => $root->nama ?: 'Kategori #'.$root->id,
                'slug' => Str::slug($root->nama ?: 'kategori').'-'.Str::lower(Str::random(6)),
                'description' => $root->deskripsi,
                'sort_order' => $index,
                'created_by' => $root->user_id,
                'legacy_category_id' => $root->id,
                'created_at' => $root->created_at ?? $now,
                'updated_at' => $root->updated_at ?? $now,
            ]);

            $this->insertNodes($childrenByParent, $root->id, $rootId, null, null, 1);
        }

        $this->report['categories_v3'] = DB::table('categories_v3')->count();
        $this->report['category_nodes'] = DB::table('category_nodes')->count();
    }

    /**
     * @param  Collection<int|string, Collection<int, object>>  $childrenByParent
     */
    private function insertNodes($childrenByParent, int $legacyParentId, string $categoryId, ?string $parentNodeId, ?string $parentPath, int $depth): void
    {
        $now = now();

        foreach ($childrenByParent->get($legacyParentId, collect())->values() as $index => $child) {
            $nodeId = (string) Str::uuid();
            $path = ltrim($parentPath.'.n'.str_replace('-', '', $nodeId), '.');

            DB::table('category_nodes')->insert([
                'id' => $nodeId,
                'category_id' => $categoryId,
                'parent_id' => $parentNodeId,
                'name' => $child->nama ?: 'Kategori #'.$child->id,
                'slug' => Str::slug($child->nama ?: 'kategori').'-'.Str::lower(Str::random(6)),
                'description' => $child->deskripsi,
                'depth' => $depth,
                'sort_order' => $index,
                'created_by' => $child->user_id,
                'path' => DB::raw("'{$path}'::ltree"),
                'legacy_category_id' => $child->id,
                'created_at' => $child->created_at ?? $now,
                'updated_at' => $child->updated_at ?? $now,
            ]);

            $this->insertNodes($childrenByParent, $child->id, $categoryId, $nodeId, $path, $depth + 1);
        }
    }

    /**
     * Satu Layer per kategori lama yang punya data spasial, ditempel ke node
     * kategori itu (category_id selalu root, lihat SpatialLayer). Warna, ikon,
     * marker, dan gambar kategori pindah ke style default Layer.
     *
     * Semua Layer `published`: peta publik lama (/geojson) menampilkan data
     * semua kategori tanpa melihat categories.is_active — kolom itu dulu
     * hanya memilih kartu peta unggulan di beranda.
     */
    private function buildLayers(): void
    {
        $categories = DB::table('categories')
            ->whereIn('id', DB::table('data_spatial')->select('kategori_id')->whereNotNull('kategori_id'))
            ->orderBy('id')
            ->get();
        $roots = DB::table('categories_v3')->pluck('id', 'legacy_category_id');
        $nodes = DB::table('category_nodes')->get(['id', 'category_id', 'legacy_category_id'])->keyBy('legacy_category_id');
        $dataYears = DB::table('data_spatial')
            ->whereBetween('tahun', [1900, 2100])
            ->groupBy('kategori_id')
            ->selectRaw('kategori_id, max(tahun) as data_year')
            ->pluck('data_year', 'kategori_id');
        $now = now();

        foreach ($categories as $index => $category) {
            $node = $nodes->get($category->id);
            $categoryId = $node->category_id ?? $roots->get($category->id);
            $layerId = (string) Str::uuid();
            $styleId = (string) Str::uuid();

            DB::table('layers')->insert([
                'id' => $layerId,
                'category_id' => $categoryId,
                'category_node_id' => $node->id ?? null,
                'layer_type_id' => 4,
                'code' => 'layer-'.Str::lower(Str::random(10)),
                'name' => $category->nama ?: 'Layer #'.$category->id,
                'slug' => $this->uniqueLayerSlug($category->nama ?: 'layer'),
                'short_description' => $category->deskripsi ? Str::limit($category->deskripsi, 250, '') : null,
                'storage_srid' => 4326,
                'status' => 'published',
                'published_at' => $category->created_at ?? $now,
                'default_opacity' => 1,
                'sort_order' => $index,
                'created_by' => $category->user_id,
                'legacy_category_id' => $category->id,
                'created_at' => $category->created_at ?? $now,
                'updated_at' => $category->updated_at ?? $now,
            ]);

            DB::table('layer_styles')->insert([
                'id' => $styleId,
                'layer_id' => $layerId,
                'name' => 'Default',
                'style_type' => 'simple',
                'is_default' => true,
                'definition' => json_encode(array_filter([
                    'color' => $category->warna ?: '#2563eb',
                    'icon' => $category->icon ?: null,
                    'is_marker' => (bool) $category->is_marker,
                    'opacity' => 1,
                    'thumbnail' => $category->gambar ?: null,
                ], fn ($value) => $value !== null)),
                'created_by' => $category->user_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('layers')->where('id', $layerId)->update(['default_style_id' => $styleId]);

            DB::table('layer_metadata')->insert([
                'layer_id' => $layerId,
                'title' => $category->nama,
                'data_year' => $dataYears->get($category->id),
                'updated_at' => $now,
            ]);
        }

        $this->report['layers'] = DB::table('layers')->count();
        $this->report['layer_styles'] = DB::table('layer_styles')->count();
    }

    /**
     * Sama dengan SpatialFeaturesV3Sync lama: properties = dbf_attributes +
     * sumber_data/opd_penanggung_jawab/tanggal_data/tahun, geometri dirapikan
     * (2D, valid) karena CHECK ck_spatial_features_v3_valid.
     *
     * Sebagian data lama berkoordinat Web Mercator (meter) tapi ditandai SRID
     * 4326 — direproyeksi dari 3857. Geometri yang tetap di luar rentang
     * derajat dilewati: kolom generated area_m2/length_m (geography) gagal
     * dihitung untuk koordinat seperti itu.
     */
    private function copySpatialFeatures(): void
    {
        DB::statement("
            INSERT INTO spatial_features
                (layer_id, geom, properties, gambar, created_by, legacy_data_spatial_id, created_at, updated_at)
            SELECT l.id, src.geom, src.properties, src.gambar, src.user_id, src.id, src.created_at, now()
            FROM (
                SELECT ds.id, ds.kategori_id, ds.gambar, ds.user_id, ds.created_at,
                    ST_MakeValid(CASE
                        WHEN ST_XMin(ds.geom) >= -180 AND ST_XMax(ds.geom) <= 180 AND ST_YMin(ds.geom) >= -90 AND ST_YMax(ds.geom) <= 90
                            THEN ST_Force2D(ds.geom)
                        WHEN ST_XMin(ds.geom) >= -20037508.35 AND ST_XMax(ds.geom) <= 20037508.35 AND ST_YMin(ds.geom) >= -20048966.11 AND ST_YMax(ds.geom) <= 20048966.11
                            THEN ST_Transform(ST_SetSRID(ST_Force2D(ds.geom), 3857), 4326)
                    END) AS geom,
                    CASE WHEN jsonb_typeof(ds.dbf_attributes) = 'object' THEN ds.dbf_attributes ELSE '{}'::jsonb END
                    || jsonb_strip_nulls(jsonb_build_object(
                        'sumber_data', ds.sumber_data,
                        'opd_penanggung_jawab', o.name,
                        'tanggal_data', ds.tanggal_data::text,
                        'tahun', ds.tahun
                    )) AS properties
                FROM data_spatial ds
                LEFT JOIN opd o ON o.id = ds.opd_pengelola_id
                WHERE ds.geom IS NOT NULL
            ) src
            JOIN layers l ON l.legacy_category_id = src.kategori_id
            WHERE src.geom IS NOT NULL AND NOT ST_IsEmpty(src.geom) AND ST_IsValid(src.geom)
                AND ST_XMin(src.geom) >= -180 AND ST_XMax(src.geom) <= 180 AND ST_YMin(src.geom) >= -90 AND ST_YMax(src.geom) <= 90
        ");

        $this->report['spatial_features'] = DB::table('spatial_features')->count();

        $skipped = DB::table('data_spatial')->count() - $this->report['spatial_features'];

        if ($skipped > 0) {
            $this->warn("{$skipped} baris data_spatial tidak dibuatkan spatial_features (tanpa kategori, atau geometri kosong/tidak valid/di luar rentang koordinat).");
        }
    }

    /**
     * Tipe Layer dari geometri yang benar-benar tersimpan, lalu cache
     * feature_count/bbox (sama dengan SpatialLayer::refreshFeatureCache()).
     */
    private function finalizeLayers(): void
    {
        DB::statement("
            UPDATE layers l
            SET layer_type_id = lt.id, geometry_type = lt.geometry_type
            FROM (
                SELECT layer_id,
                    CASE
                        WHEN bool_and(geom_type IN ('ST_Point', 'ST_MultiPoint')) THEN 'vector_point'
                        WHEN bool_and(geom_type IN ('ST_LineString', 'ST_MultiLineString')) THEN 'vector_line'
                        WHEN bool_and(geom_type IN ('ST_Polygon', 'ST_MultiPolygon')) THEN 'vector_polygon'
                        ELSE 'vector_mixed'
                    END AS code
                FROM spatial_features
                GROUP BY layer_id
            ) kinds
            JOIN layer_types lt ON lt.code = kinds.code
            WHERE l.id = kinds.layer_id
        ");

        DB::statement('
            UPDATE layers l
            SET feature_count = sub.cnt, bbox = sub.bbox
            FROM (
                SELECT layer_id, COUNT(*) AS cnt, ST_Envelope(ST_Collect(geom)) AS bbox
                FROM spatial_features
                GROUP BY layer_id
            ) sub
            WHERE l.id = sub.layer_id
        ');
    }

    private function uniqueLayerSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'layer';
        $slug = $base;
        $counter = 1;

        while (isset($this->usedSlugs[$slug])) {
            $slug = $base.'-'.$counter++;
        }

        $this->usedSlugs[$slug] = true;

        return $slug;
    }

    private function resetSequence(string $table): void
    {
        DB::statement(
            "SELECT setval(pg_get_serial_sequence(?, 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 0) + 1, false)",
            [$table]
        );
    }

    private function legacyHasTable(string $table): bool
    {
        return $this->legacyColumns($table) !== [];
    }

    /**
     * @return list<string>
     */
    private function legacyColumns(string $table): array
    {
        return $this->columnsOf($this->legacy, $table);
    }

    /**
     * @return list<string>
     */
    private function targetColumns(string $table): array
    {
        return $this->columnsOf(DB::connection(), $table);
    }

    /**
     * @return list<string>
     */
    private function legacyGeometryColumns(string $table): array
    {
        return array_column($this->legacy->select(
            "SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND udt_name = 'geometry'",
            [$this->schemaOf($this->legacy), $table]
        ), 'column_name');
    }

    /**
     * @return list<string>
     */
    private function columnsOf(Connection $connection, string $table): array
    {
        return array_column($connection->select(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position',
            [$this->schemaOf($connection), $table]
        ), 'column_name');
    }

    /**
     * Schema pertama di search_path koneksi (default `public`).
     */
    private function schemaOf(Connection $connection): string
    {
        $searchPath = $connection->getConfig('search_path') ?: 'public';
        $schemas = is_array($searchPath) ? $searchPath : explode(',', $searchPath);

        return trim((string) $schemas[0], " \t\"'") ?: 'public';
    }
}
