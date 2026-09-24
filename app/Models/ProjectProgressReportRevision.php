<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProgressReportRevision extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_progress_report_id',
        'data_sebelumnya',
        'diperbarui_oleh',
    ];

    protected function casts(): array
    {
        return [
            'data_sebelumnya' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ProjectProgressReport::class, 'project_progress_report_id');
    }

    public function diperbaruiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperbarui_oleh');
    }
}
