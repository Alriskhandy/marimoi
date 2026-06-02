<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'description'    => $this->description,
            'category'       => $this->category,
            'file_name'      => $this->file_name,
            'file_path'      => $this->file_url,
            'file_type'      => $this->file_type,
            'file_size'      => $this->getFileSizeFormatted(),
            'file_cover'     => $this->cover ? asset('storage/' . $this->cover) : null,
            'download_count' => $this->download_count,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
