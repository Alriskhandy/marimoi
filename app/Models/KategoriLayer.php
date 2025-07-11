<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriLayer extends Model
{
    protected $fillable = ['nama', 'deskripsi', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(KategoriLayer::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(KategoriLayer::class, 'parent_id');
    }

    public function lokasis(): HasMany
    {
        return $this->hasMany(Lokasi::class, 'kategori_id');
    }
}
