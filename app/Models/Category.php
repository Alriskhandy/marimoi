<?php

namespace App\Models;

use App\Support\MapDataVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Kategori, hirarki flat 3 level (root/child/grandchild) via `parent_id` —
 * sejak Fase 3 lanjutan (plan mellow-weaving-eclipse) disimpan di DUA tabel
 * v3 ("Keputusan Desain Kunci" #4): root di `categories_v3`, turunannya di
 * `category_nodes` (yang juga punya `path` ltree, tidak dipakai di sini).
 *
 * Supaya CategoryController & view admin (categories/index.blade.php) yang
 * sudah ada tidak perlu ditulis ulang total, model ini DIBACA dari view SQL
 * `categories_tree_v3` (lihat migration create_categories_tree_v3_view) yang
 * menyatukan kedua tabel dengan nama kolom sama seperti `categories` v2 lama.
 *
 * View adalah UNION ALL biasa (bukan writable view/trigger) — create()/
 * save()/delete() di-override total di sini untuk menulis langsung ke tabel
 * yang benar (categories_v3 kalau root, category_nodes kalau punya parent),
 * memakai `source_table` (kolom tambahan di view) untuk tahu asal baris saat
 * update/delete instance existing.
 *
 * `data_spatial_legacy_v1.kategori_id` adalah FK bigint ke tabel v1/v2
 * `categories_legacy_v1` (bukan ke sini) — lihat DataSpatial::kategori(),
 * yang sengaja query langsung DB::table('categories_legacy_v1'), bukan
 * kelas ini.
 */
class Category extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'categories_tree_v3';

    protected $keyType = 'string';

    protected $fillable = [
        'type', 'nama', 'warna', 'icon', 'is_marker', 'user_id',
        'deskripsi', 'parent_id', 'is_active', 'gambar', 'sort_order',
    ];

    protected $casts = [
        'is_marker' => 'boolean',
        'is_active' => 'boolean',
        'depth' => 'integer',
        'sort_order' => 'integer',
    ];

    public static function create(array $attributes = []): self
    {
        $id = (string) Str::uuid();
        $now = now();
        $slug = Str::slug($attributes['nama']).'-'.Str::lower(Str::random(6));

        if (empty($attributes['parent_id'])) {
            DB::table('categories_v3')->insert([
                'id' => $id,
                'code' => 'cat-'.Str::lower(Str::random(10)),
                'name' => $attributes['nama'],
                'slug' => $slug,
                'type' => $attributes['type'] ?? null,
                'description' => $attributes['deskripsi'] ?? null,
                'icon' => $attributes['icon'] ?? null,
                'color' => $attributes['warna'] ?? null,
                'is_marker' => $attributes['is_marker'] ?? false,
                'is_active' => $attributes['is_active'] ?? false,
                'gambar' => $attributes['gambar'] ?? null,
                'sort_order' => $attributes['sort_order'] ?? 0,
                'created_by' => $attributes['user_id'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            [$categoryId, $parentNodeId, $depth] = self::resolveParentNode($attributes['parent_id']);
            $parentPath = $parentNodeId
                ? DB::table('category_nodes')->where('id', $parentNodeId)->value('path')
                : null;
            $label = self::ltreeLabel($id);
            $path = $parentPath ? "{$parentPath}.{$label}" : $label;

            DB::table('category_nodes')->insert([
                'id' => $id,
                'category_id' => $categoryId,
                'parent_id' => $parentNodeId,
                'name' => $attributes['nama'],
                'slug' => $slug,
                'description' => $attributes['deskripsi'] ?? null,
                'icon' => $attributes['icon'] ?? null,
                'color' => $attributes['warna'] ?? null,
                'depth' => $depth,
                'is_active' => $attributes['is_active'] ?? false,
                'gambar' => $attributes['gambar'] ?? null,
                'is_marker' => $attributes['is_marker'] ?? false,
                'sort_order' => $attributes['sort_order'] ?? 0,
                'created_by' => $attributes['user_id'] ?? null,
                'path' => DB::raw("'{$path}'::ltree"),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        self::bustTreeCache();

        return self::findOrFail($id);
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        $now = now();

        if ($this->source_table === 'categories_v3') {
            DB::table('categories_v3')->where('id', $this->id)->update([
                'type' => $attributes['type'] ?? $this->type,
                'name' => $attributes['nama'] ?? $this->nama,
                'description' => array_key_exists('deskripsi', $attributes) ? $attributes['deskripsi'] : $this->deskripsi,
                'icon' => array_key_exists('icon', $attributes) ? $attributes['icon'] : $this->icon,
                'color' => array_key_exists('warna', $attributes) ? $attributes['warna'] : $this->warna,
                'is_marker' => $attributes['is_marker'] ?? $this->is_marker,
                'is_active' => $attributes['is_active'] ?? $this->is_active,
                'gambar' => array_key_exists('gambar', $attributes) ? $attributes['gambar'] : $this->gambar,
                'sort_order' => $attributes['sort_order'] ?? $this->sort_order,
                'updated_at' => $now,
            ]);
        } else {
            $isMoving = isset($attributes['parent_id']) && (string) $attributes['parent_id'] !== (string) $this->parent_id;

            [$categoryId, $parentNodeId, $depth] = isset($attributes['parent_id'])
                ? self::resolveParentNode($attributes['parent_id'])
                : [null, null, null];
            $newPath = null;

            $update = [
                'name' => $attributes['nama'] ?? $this->nama,
                'description' => array_key_exists('deskripsi', $attributes) ? $attributes['deskripsi'] : $this->deskripsi,
                'icon' => array_key_exists('icon', $attributes) ? $attributes['icon'] : $this->icon,
                'color' => array_key_exists('warna', $attributes) ? $attributes['warna'] : $this->warna,
                'is_marker' => $attributes['is_marker'] ?? $this->is_marker,
                'is_active' => $attributes['is_active'] ?? $this->is_active,
                'gambar' => array_key_exists('gambar', $attributes) ? $attributes['gambar'] : $this->gambar,
                'sort_order' => $attributes['sort_order'] ?? $this->sort_order,
                'updated_at' => $now,
            ];

            if ($categoryId !== null) {
                $update['category_id'] = $categoryId;
                $update['parent_id'] = $parentNodeId;
                $update['depth'] = $depth;

                // "Move node" (§5.1 butir 2) — path ltree node ini SENDIRI juga
                // ikut dihitung ulang (bukan cuma parent_id/depth), dan setiap
                // kali pindah, seluruh keturunannya (yang parent_id-nya sendiri
                // TIDAK berubah) harus ikut mendapat category_id/depth/path baru
                // berdasarkan posisi barunya — sebelumnya field ini ditinggal
                // basi, cuma parent_id node teratas yang berubah.
                $parentPath = $parentNodeId ? DB::table('category_nodes')->where('id', $parentNodeId)->value('path') : null;
                $newPath = $parentPath ? "{$parentPath}.".self::ltreeLabel($this->id) : self::ltreeLabel($this->id);
                $update['path'] = DB::raw("'{$newPath}'::ltree");
            }

            DB::transaction(function () use ($update, $isMoving, $categoryId, $newPath) {
                DB::table('category_nodes')->where('id', $this->id)->update($update);

                if ($isMoving && $categoryId !== null) {
                    self::cascadeMoveToDescendants($this->id, $newPath, $update['depth'], $categoryId);
                }
            });
        }

        self::bustTreeCache();
        $this->setRawAttributes(self::query()->findOrFail($this->id)->getAttributes());

        return true;
    }

    public function delete(): bool
    {
        DB::table($this->source_table)->where('id', $this->id)->delete();
        self::bustTreeCache();

        return true;
    }

    /**
     * @return array{0: string, 1: ?string, 2: int} [category_id (root), parent_node_id, depth]
     */
    private static function resolveParentNode(string $parentId): array
    {
        $root = DB::table('categories_v3')->where('id', $parentId)->first();
        if ($root) {
            return [$root->id, null, 1];
        }

        $node = DB::table('category_nodes')->where('id', $parentId)->firstOrFail();

        return [$node->category_id, $node->id, $node->depth + 1];
    }

    /**
     * Setelah node pindah, turunannya (parent_id-nya sendiri tidak berubah,
     * cuma posisi leluhurnya) butuh category_id/depth/path baru juga — jalan
     * rekursif turun dari node yang baru dipindah, murni dari parent_id yang
     * SUDAH ada (bukan menghitung ulang dari path lama), supaya konsisten
     * dengan posisi baru di pohon.
     */
    private static function cascadeMoveToDescendants(string $parentId, string $parentPath, int $parentDepth, string $rootCategoryId): void
    {
        $children = DB::table('category_nodes')->where('parent_id', $parentId)->get(['id']);

        foreach ($children as $child) {
            $childPath = "{$parentPath}.".self::ltreeLabel($child->id);
            $childDepth = $parentDepth + 1;

            DB::table('category_nodes')->where('id', $child->id)->update([
                'category_id' => $rootCategoryId,
                'depth' => $childDepth,
                'path' => DB::raw("'{$childPath}'::ltree"),
                'updated_at' => now(),
            ]);

            self::cascadeMoveToDescendants($child->id, $childPath, $childDepth, $rootCategoryId);
        }
    }

    private static function ltreeLabel(string $uuid): string
    {
        return 'n'.str_replace('-', '', $uuid);
    }

    private static function bustTreeCache(): void
    {
        foreach (['', '.tematik', '.usulan_musrenbang', '.pokir_dprd', '.psd', '.psn'] as $suffix) {
            Cache::forget('api.v1.layers.tree'.$suffix);
        }

        MapDataVersion::forget();
    }

    /**
     * ID kategori ini beserta seluruh turunannya (anak, cucu, dst.).
     *
     * @return array<int, string>
     */
    public static function selfAndDescendantIds(string $id): array
    {
        $ids = [$id];
        $frontier = [$id];

        while ($frontier !== []) {
            $frontier = static::whereIn('parent_id', $frontier)
                ->whereNotIn('id', $ids)
                ->pluck('id')
                ->all();

            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('nama');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * `data_spatial_legacy_v1.kategori_id` adalah FK bigint ke tabel v1/v2
     * `categories_legacy_v1` (lihat DataSpatial::kategori()) — bukan ke sini,
     * jadi tidak bisa jadi relasi Eloquent biasa (tipe kolom beda, uuid vs
     * bigint). Data nyata kategori v3 ada di `layers` (lewat category_id
     * ATAU category_node_id) sejak Fase 2 migrasi.
     */
    public function hasLinkedLayers(): bool
    {
        return DB::table('layers')
            ->where('category_id', $this->id)
            ->orWhere('category_node_id', $this->id)
            ->exists();
    }

    public function scopeLayers($query)
    {
        return $query->where('type', 'tematik');
    }

    public function scopeMusenbangs($query)
    {
        return $query->where('type', 'usulan_musrenbang');
    }

    public function scopePokirDprds($query)
    {
        return $query->where('type', 'pokir_dprd');
    }

    public function scopePsd($query)
    {
        return $query->where('type', 'psd');
    }

    public function scopePsn($query)
    {
        return $query->where('type', 'psn');
    }

    public function scopeMarkers($query)
    {
        return $query->where('is_marker', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
