<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class LayerService
{
    public function getTree(?string $type = null): Collection
    {
        $cacheKey = 'api.v1.layers.tree' . ($type ? ".{$type}" : '');

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($type) {
            $query = Category::roots();

            if ($type) {
                $query->where('type', $type);
            }

            return $query
                ->with('children')
                ->get();
        });
    }

    public function findById(int $id): Category
    {
        return Category::with('children')->findOrFail($id);
    }
}
