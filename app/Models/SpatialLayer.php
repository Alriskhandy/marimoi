<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Skema v3 §5.4 — tabel fisik `layers_v3` (nama final `layers` menyusul di
 * Fase 6 cutover, lihat plan mellow-weaving-eclipse). Nama kelas PHP
 * dipertahankan `SpatialLayer` (bukan `Layer` sesuai dokumen) supaya
 * route-model-binding `{spatialLayer}` dan seluruh controller/view/test yang
 * sudah memakai nama ini tidak perlu di-rename (plan Keputusan #1).
 *
 * Hirarki parent-child layer v2 (self-reference parent_id) DIHAPUS — di v3,
 * organisasi/hirarki adalah tanggung jawab categories_v3/category_nodes
 * (category_id/category_node_id di bawah), bukan lagi antar-layer. Relasi
 * `category()`/`categoryGroup()` SENGAJA belum ada: nama kelas `Category`
 * masih dipakai model lama (tabel `categories` bigint) sampai direwrite
 * bersamaan (plan Fase 3 bagian Kategori) — category_id dipakai langsung
 * sebagai kolom biasa untuk sementara.
 *
 * Sejak 2026-10-06, Layer TIDAK LAGI terhubung ke `map_types` (kolom
 * `map_type_id` dihapus, lihat migration
 * drop_map_type_id_and_visibility_from_layers_table) — relasi `mapType()`
 * dibuang. Konsekuensinya, sistem "Metadata Dinamis per Jenis Peta"
 * (MapTypeDynamicAttribute) SELALU kosong untuk semua Layer (konsumennya
 * sudah null-safe lewat `if (! $layer->map_type_id)`, jadi tidak error, cuma
 * tidak pernah menampilkan apa pun lagi). `visibility` juga dihapus — field
 * itu memang tidak pernah ditegakkan untuk kontrol akses di mana pun.
 */
class SpatialLayer extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'layers';

    protected $keyType = 'string';

    protected $fillable = [
        'category_id',
        'category_node_id',
        'layer_type_id',
        'opd_id',
        'code',
        'name',
        'slug',
        'short_description',
        'geometry_type',
        'storage_srid',
        'default_style_id',
        'status',
        'default_opacity',
        'sort_order',
        'published_at',
        'created_by',
        'updated_by',
        'legacy_spatial_layer_id',
        'legacy_category_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'default_opacity' => 'float',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $layer) {
            $layer->id ??= (string) Str::uuid();
        });
    }

    /**
     * OPD pemilik layer (R19–R21, D16). `NULL` berarti milik provinsi/Bappeda.
     */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function layerType(): BelongsTo
    {
        return $this->belongsTo(LayerType::class);
    }

    public function categoryNode(): BelongsTo
    {
        return $this->belongsTo(CategoryNode::class, 'category_node_id');
    }

    public function metadata(): HasOne
    {
        return $this->hasOne(SpatialLayerMetadata::class, 'layer_id');
    }

    public function features(): HasMany
    {
        return $this->hasMany(SpatialLayerFeature::class, 'layer_id');
    }

    public function styles(): HasMany
    {
        return $this->hasMany(LayerStyle::class, 'layer_id');
    }

    public function defaultStyle(): BelongsTo
    {
        return $this->belongsTo(LayerStyle::class, 'default_style_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(LayerSource::class, 'layer_id');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(LayerImport::class, 'layer_id');
    }

    /**
     * Hitung ulang `feature_count`/`bbox` dari `spatial_features` (R18) —
     * dulu hanya dilakukan oleh App\Support\SpatialFeaturesV3Sync untuk jalur
     * tulis DataSpatialController lama; jalur admin baru (Import/CRUD fitur
     * lewat SpatialLayerFeatureController) perlu memanggil ini sendiri setiap
     * kali fiturnya berubah (bukan cuma update()/destroy() biasa, karena tidak
     * ada model event yang otomatis terpicu untuk operasi batch).
     */
    public function refreshFeatureCache(): void
    {
        DB::update(
            <<<'SQL'
                UPDATE layers lv
                SET feature_count = sub.cnt, bbox = sub.bbox
                FROM (
                    SELECT COUNT(*) AS cnt, ST_Envelope(ST_Collect(geom)) AS bbox
                    FROM spatial_features WHERE layer_id = ?
                ) sub
                WHERE lv.id = ?
                SQL,
            [$this->id, $this->id]
        );
    }

    /**
     * Aksesor transparan ke style default — color/icon/is_marker/opacity dulu
     * kolom langsung di spatial_layers (v2), sekarang pindah ke layer_styles
     * v3 (style terpisah dari layer). Aksesor ini supaya view lama
     * ($layer->color dst.) tidak perlu ditulis ulang satu-satu. Perlu eager
     * load relasi `defaultStyle` di controller untuk menghindari N+1.
     */
    public function getColorAttribute(): ?string
    {
        return $this->defaultStyle?->definition['color'] ?? null;
    }

    public function getIconAttribute(): ?string
    {
        return $this->defaultStyle?->definition['icon'] ?? null;
    }

    public function getIsMarkerAttribute(): bool
    {
        return (bool) ($this->defaultStyle?->definition['is_marker'] ?? false);
    }

    public function getOpacityAttribute(): float
    {
        return (float) ($this->default_opacity ?? 1);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'published';
    }
}
