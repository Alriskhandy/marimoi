<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriAspirasi extends Model
{
    use HasFactory;
protected $table = 'kategori_aspirasi';
    protected $fillable = [
        'opd_id',
        'nama_kategori',
        'deskripsi',
    ];

    // protected $casts = [
    //     'is_active' => 'boolean',
    // ];

    // Relationships
    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    public function aspirasi()
    {
        return $this->hasMany(Aspirasi::class);
    }

    // Scopes
    // public function scopeActive($query)
    // {
    //     return $query->where('is_active', true);
    // }

    // Mutators
    // public function setKodeKategoriAttribute($value)
    // {
    //     $this->attributes['kode_kategori'] = strtoupper($value);
    // }
}