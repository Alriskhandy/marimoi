<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Skema v3 §5.2 — pohon subkategori ber-ltree di bawah `categories_v3`.
 *
 * SENGAJA belum punya relasi Eloquent `category()` ke root-nya: nama kelas
 * `Category` masih dipakai model lama (tabel `categories` bigint) yang aktif
 * dipakai banyak controller (CategoryController, DataSpatialController,
 * FrontendController, dst.) sampai direwrite bersamaan (plan Fase 3 bagian
 * Kategori). Sebelum itu, `category_id` dipakai langsung sebagai kolom biasa
 * (query lewat DB::table('categories_v3')) supaya tidak menabrak model lama.
 */
class CategoryNode extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'category_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'depth',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
        'legacy_category_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $node) {
            $node->id ??= (string) Str::uuid();
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function layers(): HasMany
    {
        return $this->hasMany(SpatialLayer::class, 'category_node_id');
    }
}
