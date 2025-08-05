<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opd extends Model
{
    use HasFactory;
protected $table = 'opd';
    protected $fillable = [
        'name',
        'singkatan',
        'logo',
        'alamat',
        'telepon',
        'email',
        'website',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function kategoriAspirasi()
    {
        return $this->hasMany(KategoriAspirasi::class);
    }

    public function aspirasi()
    {
        return $this->hasManyThrough(Aspirasi::class, KategoriAspirasi::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}