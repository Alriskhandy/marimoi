<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'parent_id'   => $this->parent_id,
            'type'        => $this->type,
            'name'        => $this->nama,
            'description' => $this->deskripsi,
            'color'       => $this->warna,
            'icon'        => $this->icon,
            'is_marker'   => $this->is_marker,
            'is_active'   => $this->is_active,
            'img_path'    => $this->gambar ? asset('storage/' . $this->gambar) : null,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
            'child'       => LayerResource::collection($this->whenLoaded('children')),
        ];
    }
}
