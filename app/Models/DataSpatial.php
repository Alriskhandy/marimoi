<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Model utama untuk data spatial
class DataSpatial extends Model
{
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            do {
                $randomNumber = str_pad(random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
                $uuid = 'MARIMOI-' . $randomNumber;
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
        });
    }

    protected $table = 'data_spatial';
    
    protected $fillable = [
        'data_type',
        'sub_type',
        'kategori_id',
        'deskripsi',
        'dbf_attributes',
        'tahun',
        'gambar',
        'views',
        'geom',
        'user_id'
    ];

    protected $casts = [
        'dbf_attributes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relasi ke kategori
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'kategori_id');
    }

    // === SCOPES UNTUK DATA TYPE ===
    
    public function scopeLokasi($query)
    {
        return $query->where('data_type', 'tematik');
    }

    public function scopeUsulanMusrenbang($query)
    {
        return $query->where('data_type', 'usulan_musrenbang');
    }

    public function scopePokirDprd($query)
    {
        return $query->where('data_type', 'pokir_dprd');
    }

    public function scopeProyekStrategis($query)
    {
        return $query->where('data_type', 'proyek_strategis');
    }

    // === SCOPES UNTUK SUB TYPE ===
    
    public function scopeProyekStrategisDaerah($query)
    {
        return $query->proyekStrategis()->where('sub_type', 'psd');
    }

    public function scopeProyekStrategisNasional($query)
    {
        return $query->proyekStrategis()->where('sub_type', 'psn');
    }

    // === SCOPES TAMBAHAN ===
    
    public function scopeByYear($query, $year)
    {
        return $query->where('tahun', $year);
    }

    public function scopeHasGeometry($query)
    {
        return $query->whereNotNull('geom');
    }

    public function scopeWithoutGeometry($query)
    {
        return $query->whereNull('geom');
    }

    // === HELPER METHODS ===
    
    public function isLokasi()
    {
        return $this->data_type === 'tematik';
    }

    public function isProposal()
    {
        return in_array($this->data_type, ['usulan_musrenbang', 'pokir_dprd']);
    }

    public function isProyekStrategis()
    {
        return $this->data_type === 'proyek_strategis';
    }

    public function hasGeometry()
    {
        return !is_null($this->geom);
    }

    // Method untuk mendapatkan nama kategori
    public function getKategoriNameAttribute()
    {
        return $this->kategori?->nama;
    }

    // Method untuk format display type
    public function getDisplayTypeAttribute()
    {
        $type = match($this->data_type) {
            'tematik' => 'Tematik',
            'usulan_musrenbang' => 'Usulan Musrenbang',
            'pokir_dprd' => 'Pokir DPRD',
            'proyek_strategis' => 'Proyek Strategis',
            default => ucwords(str_replace('_', ' ', $this->data_type))
        };

        if ($this->sub_type) {
            $type .= ' ' . ucfirst($this->sub_type);
        }

        return $type;
    }

    // Method untuk mendapatkan nama kategori type berdasarkan data_type
    public function getCategoryTypeAttribute()
    {
        return match($this->data_type) {
            'tematik' => 'tematik',
            'usulan_musrenbang' => 'usulan_musrenbang',
            'pokir_dprd' => 'pokir_dprd',
            'proyek_strategis' => $this->sub_type === 'psd' ? 'psd' : 'psn',
            default => 'tematik'
        };
    }

    /**
     * Accessor untuk mendapatkan atribut DBF tertentu
     */
    public function getDbfAttribute($key)
    {
        return $this->dbf_attributes[$key] ?? null;
    }

    /**
     * Mendapatkan semua nama kolom dari atribut DBF
     */
    public function getDbfColumns()
    {
        return $this->dbf_attributes ? array_keys($this->dbf_attributes) : [];
    }

    /**
     * Scope untuk filtering berdasarkan atribut DBF (PostgreSQL JSONB)
     */
    public function scopeWhereDbfAttribute($query, $attribute, $value)
    {
        return $query->whereRaw("dbf_attributes->? = ?", [$attribute, json_encode($value)]);
    }

    /**
     * Scope untuk pencarian dalam atribut DBF (PostgreSQL JSONB)
     */
    public function scopeSearchDbfAttributes($query, $search)
    {
        return $query->whereRaw("dbf_attributes::text ILIKE ?", ["%{$search}%"]);
    }

    /**
     * Scope untuk pencarian berdasarkan path dalam JSONB
     */
    public function scopeWhereDbfPath($query, $path, $value)
    {
        return $query->whereRaw("dbf_attributes #> ? = ?", ['{' . $path . '}', json_encode($value)]);
    }

    /**
     * Scope untuk filtering dengan operator JSONB
     */
    public function scopeWhereDbfContains($query, $data)
    {
        return $query->whereRaw("dbf_attributes @> ?", [json_encode($data)]);
    }

    public function projectFeedbacks()
{
    return $this->hasMany(ProjectFeedback::class, 'data_spatial_id');
}

}

// Model khusus untuk masing-masing jenis (opsional, untuk kemudahan akses)
class Lokasi extends DataSpatial
{
    protected static function booted()
    {
        static::addGlobalScope('tematik', function ($query) {
            $query->where('data_type', 'tematik');
        });
        
        static::creating(function ($model) {
            $model->data_type = 'tematik';
        });
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'kategori_id')
                   ->where('type', 'tematik');
    }
}

class UsulanMusrenbang extends DataSpatial
{
    protected static function booted()
    {
        static::addGlobalScope('usulan_musrenbang', function ($query) {
            $query->where('data_type', 'usulan_musrenbang');
        });
        
        static::creating(function ($model) {
            $model->data_type = 'usulan_musrenbang';
        });
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'kategori_id')
                   ->where('type', 'usulan_musrenbang');
    }
}

class PokirDprd extends DataSpatial
{
    protected static function booted()
    {
        static::addGlobalScope('pokir_dprd', function ($query) {
            $query->where('data_type', 'pokir_dprd');
        });
        
        static::creating(function ($model) {
            $model->data_type = 'pokir_dprd';
        });
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'kategori_id')
                   ->where('type', 'pokir_dprd');
    }
}

class ProyekStrategisDaerah extends DataSpatial
{
    protected static function booted()
    {
        static::addGlobalScope('proyek_strategis_daerah', function ($query) {
            $query->where('data_type', 'proyek_strategis')
                  ->where('sub_type', 'psd');
        });
        
        static::creating(function ($model) {
            $model->data_type = 'proyek_strategis';
            $model->sub_type = 'psd';
        });
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'kategori_id')
                   ->where('type', 'psd');
    }
}

class ProyekStrategisNasional extends DataSpatial
{
    protected static function booted()
    {
        static::addGlobalScope('proyek_strategis_nasional', function ($query) {
            $query->where('data_type', 'proyek_strategis')
                  ->where('sub_type', 'psn');
        });
        
        static::creating(function ($model) {
            $model->data_type = 'proyek_strategis';
            $model->sub_type = 'psn';
        });
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'kategori_id')
                   ->where('type', 'psn');
    }
}