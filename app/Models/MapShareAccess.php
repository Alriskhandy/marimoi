<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapShareAccess extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'map_share_id',
        'accessed_at',
        'ip_hash',
        'user_agent',
        'referer',
        'response_status',
    ];

    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
        ];
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(MapShare::class, 'map_share_id');
    }
}
