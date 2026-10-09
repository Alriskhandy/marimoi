<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Katalog Peta Tematik publik dari skema V3: satu mapset = satu Layer berstatus
 * `published` (`layers`), dikelompokkan Kategori (`categories_v3`) › Node
 * (`category_nodes`, boleh bertingkat).
 *
 * Frontend (map.js/map-catalog.js) mengenali mapset dari NAMA, jadi nama
 * tampilan dibuat unik di sini (nama kembar diberi konteks node/kategorinya).
 * Nama yang sama dipakai di metadata, properti `kategori` tiap feature `/geojson`,
 * dan hasil filter supaya ketiganya selalu cocok.
 */
class PublicMapCatalog
{
    /**
     * Layer published beserta nama tampilan unik, gaya default, dan jumlah feature.
     *
     * @return Collection<int, object>
     */
    public static function layers(): Collection
    {
        // once(): dihitung sekali per request (dipakai metadata, filter, dan /geojson sekaligus).
        return once(fn () => self::loadLayers());
    }

    /**
     * @return Collection<int, object>
     */
    private static function loadLayers(): Collection
    {
        $nodes = DB::table('category_nodes')
            ->whereNull('deleted_at')
            ->get(['id', 'parent_id', 'name'])
            ->keyBy('id');

        $featureStats = DB::table('spatial_features')
            ->select('layer_id', DB::raw('count(*) as total'), DB::raw('max(updated_at) as latest'))
            ->groupBy('layer_id')
            ->get()
            ->keyBy('layer_id');

        $rows = DB::table('layers as l')
            ->join('categories_v3 as c', 'c.id', '=', 'l.category_id')
            ->leftJoin('layer_styles as ls', 'ls.id', '=', 'l.default_style_id')
            ->whereNull('l.deleted_at')
            ->whereNull('c.deleted_at')
            ->where('l.status', 'published')
            ->orderBy('c.sort_order')
            ->orderBy('c.name')
            ->orderBy('l.sort_order')
            ->orderBy('l.name')
            ->get([
                'l.id',
                'l.name',
                'l.short_description',
                'l.category_id',
                'l.category_node_id',
                'l.legacy_category_id',
                'l.updated_at',
                'c.name as category_name',
                DB::raw("ls.definition->>'color' as color"),
                DB::raw("ls.definition->>'icon' as icon"),
                DB::raw("COALESCE((ls.definition->>'is_marker')::boolean, false) as is_marker"),
                'ls.updated_at as style_updated_at',
            ]);

        $layers = $rows->map(function (object $row) use ($nodes, $featureStats): object {
            $stats = $featureStats->get($row->id);
            $timestamps = array_filter([$row->updated_at, $row->style_updated_at, $stats?->latest]);

            return (object) [
                'id' => $row->id,
                'name' => trim($row->name),
                'category_id' => $row->category_id,
                'category_name' => $row->category_name,
                'node_id' => $row->category_node_id,
                'node_path' => self::nodePath($row->category_node_id, $nodes),
                'color' => $row->color,
                'icon' => $row->icon,
                'is_marker' => (bool) $row->is_marker,
                'description' => $row->short_description,
                'feature_count' => (int) ($stats?->total ?? 0),
                'version' => $timestamps ? max(array_map('strtotime', $timestamps)) : 0,
                'legacy_category_id' => $row->legacy_category_id,
            ];
        });

        return self::withUniqueNames($layers);
    }

    /**
     * Payload `?metadata_only=true` dalam bentuk yang dibaca loadCategoriesMetadata()
     * di map.js: Kategori (level 1) › Node (level 2, nama = jalur node) › Layer (level 3).
     * Layer tanpa node langsung menjadi level 2 di bawah kategorinya.
     *
     * @return array{all_categories: array<int, array<string, mixed>>, root_categories: array<int, array<string, mixed>>, category_counts: array<string, int>, category_versions: array<string, int>}
     */
    public static function metadata(): array
    {
        $layers = self::layers();
        $items = [];
        $roots = [];

        foreach ($layers->groupBy('category_id') as $categoryId => $categoryLayers) {
            $rootId = 'cat-'.$categoryId;
            $root = self::item($rootId, $categoryLayers->first()->category_name, null);
            $root['children'] = [];
            $items[] = $root;

            foreach ($categoryLayers->groupBy(fn (object $layer) => $layer->node_id ?? '') as $nodeId => $nodeLayers) {
                $parentId = $rootId;

                if ($nodeId !== '') {
                    $parentId = 'node-'.$nodeId;
                    $node = self::item($parentId, implode(' › ', $nodeLayers->first()->node_path), $rootId);
                    $items[] = $node;
                    $root['children'][] = $node;
                }

                foreach ($nodeLayers as $layer) {
                    $leaf = self::item('layer-'.$layer->id, $layer->name, $parentId, [
                        'warna' => $layer->color,
                        'icon' => $layer->icon,
                        'is_marker' => $layer->is_marker,
                        'deskripsi' => $layer->description,
                    ]);
                    $items[] = $leaf;

                    if ($nodeId === '') {
                        $root['children'][] = $leaf;
                    }
                }
            }

            $roots[] = $root;
        }

        return [
            'all_categories' => $items,
            'root_categories' => $roots,
            'category_counts' => $layers->mapWithKeys(fn (object $layer) => [$layer->name => $layer->feature_count])->all(),
            'category_versions' => $layers->mapWithKeys(fn (object $layer) => [$layer->name => $layer->version])->all(),
        ];
    }

    /**
     * ID Layer published untuk daftar nama tampilan (parameter `kategori[]` di `/geojson`).
     *
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    public static function layerIdsForNames(array $names): array
    {
        $names = array_flip($names);

        return self::layers()
            ->filter(fn (object $layer) => isset($names[$layer->name]))
            ->pluck('id')
            ->values()
            ->all();
    }

    /**
     * @return array<string, string> layer id => nama tampilan
     */
    public static function namesById(): array
    {
        return self::layers()->pluck('name', 'id')->all();
    }

    /**
     * Nama mapset/kelompok V3 untuk kategori lama (tautan "Lihat peta" dari beranda masih
     * memakai ID `categories_legacy_v1`): Layer hasil migrasi kategori itu bila ada,
     * kalau tidak nama kategori/node V3 yang memetakan kategori tersebut.
     */
    public static function nameForLegacyCategory(int $legacyCategoryId): ?string
    {
        $layer = self::layers()->firstWhere('legacy_category_id', $legacyCategoryId);
        if ($layer) {
            return $layer->name;
        }

        $nodeId = DB::table('category_nodes')->whereNull('deleted_at')->where('legacy_category_id', $legacyCategoryId)->value('id');
        if ($nodeId) {
            $node = self::layers()->first(fn (object $layer) => $layer->node_id === $nodeId);

            return $node ? implode(' › ', $node->node_path) : null;
        }

        return DB::table('categories_v3')->whereNull('deleted_at')->where('legacy_category_id', $legacyCategoryId)->value('name');
    }

    /**
     * @param  Collection<string, object>  $nodes
     * @return array<int, string>
     */
    private static function nodePath(?string $nodeId, Collection $nodes): array
    {
        $path = [];
        $visited = [];

        while ($nodeId && $nodes->has($nodeId) && ! isset($visited[$nodeId])) {
            $visited[$nodeId] = true;
            $node = $nodes->get($nodeId);
            array_unshift($path, $node->name);
            $nodeId = $node->parent_id;
        }

        return $path;
    }

    /**
     * Nama kembar diberi konteks (node terdekat, lalu kategori); bila masih kembar, nomor urut.
     *
     * @param  Collection<int, object>  $layers
     * @return Collection<int, object>
     */
    private static function withUniqueNames(Collection $layers): Collection
    {
        $duplicates = $layers->countBy('name')->filter(fn (int $count) => $count > 1);
        $used = [];

        return $layers->map(function (object $layer) use ($duplicates, &$used): object {
            if ($duplicates->has($layer->name)) {
                $context = $layer->node_path ? end($layer->node_path) : $layer->category_name;
                $layer->name = "{$layer->name} ({$context})";
            }

            $base = $layer->name;
            $suffix = 2;
            while (isset($used[$layer->name])) {
                $layer->name = "{$base} {$suffix}";
                $suffix++;
            }
            $used[$layer->name] = true;

            return $layer;
        });
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private static function item(string $id, string $name, ?string $parentId, array $style = []): array
    {
        return array_merge([
            'id' => $id,
            'nama' => $name,
            'parent_id' => $parentId,
            'warna' => null,
            'icon' => null,
            'is_marker' => false,
            'deskripsi' => null,
            'gambar' => null,
        ], $style);
    }
}
