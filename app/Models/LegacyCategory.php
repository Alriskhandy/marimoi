<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @deprecated Tabel kanonik baru adalah categories_v3/category_nodes, dibaca
 * lewat `Category` (lihat app/Models/Category.php, plan mellow-weaving-eclipse
 * Fase 3 lanjutan). Kelas ini adalah `Category` versi LAMA yang dipindah ke
 * sini apa adanya karena `data_spatial.kategori_id` (FK bigint sungguhan) dan
 * `project_progress_reports.kategori_id` masih menunjuk tabel `categories`
 * lama — BUKAN sisa salinan usang, masih dipakai aktif oleh DataSpatial,
 * ProjectProgressReport, DataSpatialController, FrontendController,
 * PembangunanDashboardController, dan LayerService sampai controller-controller
 * tersebut direwrite ke skema v3 (Fase 4/5, belum dikerjakan).
 */
class LegacyCategory extends Model
{
    protected $table = 'categories_legacy_v1';

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

    public function children(): HasMany
    {
        return $this->hasMany(LegacyCategory::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(LegacyCategory::class, 'parent_id');
    }

    public function dataSpatial(): HasMany
    {
        return $this->hasMany(DataSpatial::class, 'kategori_id');
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
