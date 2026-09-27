<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @deprecated Tabel/model kanonik baru adalah SpatialLayer (lihat db-schema-v2.md,
 * Opsi A rename kanonik). Category masih dipakai penuh di banyak controller/seeder
 * (CategoryController, DataSpatialController, FrontendController, KategoriLayerSeeder,
 * dst.) sebagai compatibility source — BELUM aman dihapus/dimatikan. Lihat checklist
 * migrasi kode di docs/marimoi v2/04_implementation/09-implementasi-penuh-database-v2.md
 * Prioritas 8 sebelum benar-benar meng-retire model ini.
 */
class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'type',
        'nama',
        'warna',
        'icon',
        'is_marker',
        'user_id',
        'deskripsi',
        'parent_id',
        'is_active',
        'gambar',
    ];

    protected $casts = [
        'is_marker' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        $clearCache = function () {
            foreach (['', '.tematik', '.usulan_musrenbang', '.pokir_dprd', '.psd', '.psn'] as $suffix) {
                Cache::forget('api.v1.layers.tree'.$suffix);
            }
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    /**
     * ID kategori ini beserta seluruh turunannya (anak, cucu, dst.). Data spasial umumnya
     * disimpan pada kategori paling bawah, sehingga memfilter kategori induk harus ikut
     * mencakup turunannya.
     *
     * @return array<int, int>
     */
    public static function selfAndDescendantIds(int $id): array
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

    // Relasi hierarki
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Relasi ke data spatial
    public function dataSpatial(): HasMany
    {
        return $this->hasMany(DataSpatial::class, 'kategori_id');
    }

    // Scopes berdasarkan type
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
