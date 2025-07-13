<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KategoriPSN extends Model
{
     use HasFactory;

    protected $table = 'kategori_psn';

    protected $fillable = [
        'nama',
        'warna',
        'parent_id',
        'deskripsi',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relasi untuk parent category
    public function parent()
    {
        return $this->belongsTo(KategoriPSN::class, 'parent_id');
    }

    // Relasi untuk child categories
    public function children()
    {
        return $this->hasMany(KategoriPSN::class, 'parent_id');
    }

    // ✅ PERBAIKAN: Relasi dengan ProyekStrategisDaerah
    // Nama method harus sesuai dengan nama model (snake_case untuk relasi hasMany)
    public function proyek_strategis_nasional()
    {
        return $this->hasMany(ProyekStrategisNasional::class, 'kategori_id');
    }

    // ✅ ALTERNATIF: Bisa juga menggunakan nama yang lebih sederhana
    public function proyeks()
    {
        return $this->hasMany(ProyekStrategisNasional::class, 'kategori_id');
    }

    // ✅ ALTERNATIF: Atau nama yang lebih deskriptif
    // proyekStrategisDaerah
    public function proyekStrategisNasional()
    {
        return $this->hasMany(ProyekStrategisNasional::class, 'kategori_id');
    }

    // Scope untuk mendapatkan kategori utama (parent)
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    // Scope untuk mendapatkan sub kategori
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    // Accessor untuk mendapatkan nama lengkap dengan hierarchy
    public function getFullNameAttribute()
    {
        if ($this->parent) {
            return $this->parent->nama . ' > ' . $this->nama;
        }
        return $this->nama;
    }

    // Method untuk mendapatkan semua descendant
    public function getAllChildren()
    {
        $children = collect();
        
        foreach ($this->children as $child) {
            $children->push($child);
            $children = $children->merge($child->getAllChildren());
        }
        
        return $children;
    }

    // Method untuk mengecek apakah kategori ini memiliki proyek
    public function hasProjects()
    {
        return $this->proyeks()->exists();
        // atau: return $this->proyekStrategis()->exists();
        // atau: return $this->proyek_strategis_daerahs()->exists();
    }

    // Method untuk mendapatkan jumlah proyek per tahun
    public function getProjectCountByYear($year = null)
    {
        $query = $this->proyeks();
        
        if ($year) {
            $query->where('tahun', $year);
        }
        
        return $query->count();
    }

    // Method untuk mendapatkan proyek berdasarkan tahun
    public function getProjectsByYear($year)
    {
        return $this->proyeks()
            ->where('tahun', $year)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Method untuk mendapatkan semua tahun yang tersedia untuk kategori ini
    public function getAvailableYears()
    {
        return $this->proyeks()
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');
    }
}
