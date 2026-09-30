<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Katalog global definisi metadata (docs/marimoi v2/03_plan/
 * 14-penyesuaian-database-jenis-peta.md Bagian 6, Opsi B) — reusable lintas Jenis
 * Peta, menggantikan PHP constant `MapTypeDynamicAttribute::PLACEHOLDER_ATTRIBUTES`
 * dan definisi yang sebelumnya menyatu di `map_type_dynamic_attributes`.
 */
class MetadataDefinition extends Model
{
    public const TYPE_TEXT = 'text';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_DECIMAL = 'decimal';

    public const TYPE_CURRENCY = 'currency';

    public const TYPE_SELECT = 'select';

    public const TYPE_DATE = 'date';

    public const DATA_TYPES = [
        self::TYPE_TEXT,
        self::TYPE_INTEGER,
        self::TYPE_DECIMAL,
        self::TYPE_CURRENCY,
        self::TYPE_SELECT,
        self::TYPE_DATE,
    ];

    /**
     * 4 definisi siap-pakai bawaan (dulu `MapTypeDynamicAttribute::PLACEHOLDER_ATTRIBUTES`,
     * sekarang sumber kebenarannya baris `is_system=true` di database — konstanta ini
     * cuma dipakai untuk seed awal lewat `marimoi:migrate-metadata-definitions`).
     */
    public const SYSTEM_DEFINITIONS = [
        'pagu' => ['label' => 'Pagu', 'satuan' => 'Rp'],
        'realisasi_anggaran' => ['label' => 'Realisasi Anggaran', 'satuan' => 'Rp'],
        'realisasi_fisik' => ['label' => 'Realisasi Fisik', 'satuan' => '%'],
        'status' => ['label' => 'Status', 'satuan' => null],
    ];

    protected $fillable = [
        'kode',
        'label',
        'deskripsi',
        'data_type',
        'satuan',
        'opsi',
        'validasi',
        'is_system',
        'is_filterable',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opsi' => 'array',
            'validasi' => 'array',
            'is_system' => 'boolean',
            'is_filterable' => 'boolean',
        ];
    }

    public function mapTypeDynamicAttributes(): HasMany
    {
        return $this->hasMany(MapTypeDynamicAttribute::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
