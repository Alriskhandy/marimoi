<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;
class KategoriPokirDprd extends Model
{
     protected $fillable = ['warna','nama', 'deskripsi', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(KategoriPokirDprd::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(KategoriPokirDprd::class, 'parent_id');
    }

    public function pokirDprd(): HasMany
    {
        return $this->hasMany(PokirDprd::class, 'kategori_id');
    }
}
