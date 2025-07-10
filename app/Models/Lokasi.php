<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    use HasFactory;

    protected $fillable = [
        'kategori',
        'deskripsi',
        'dbf_attributes',
        'geom'
    ];

    protected $casts = [
        'dbf_attributes' => 'array',
    ];

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
}