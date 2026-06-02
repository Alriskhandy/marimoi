<?php

namespace App\Services;

use App\Models\Publication;
use Illuminate\Pagination\LengthAwarePaginator;

class PublicationService
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Publication::orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findById(int $id): Publication
    {
        return Publication::findOrFail($id);
    }
}
