<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriMusrenbang extends Model
{
     protected $fillable = ['warna','nama', 'deskripsi', 'parent_id','icon','is_marker'];

    public function parent()
    {
        return $this->belongsTo(KategoriMusrenbang::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(KategoriMusrenbang::class, 'parent_id');
    }

    public function usulanMusrenbang(): HasMany
    {
        return $this->hasMany(UsulanMusrenbang::class, 'kategori_id');
    }
}
