<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectProgressReport extends Model
{
    use HasFactory;

    protected $table = 'project_progress_reports';

    /**
     * @var array<int, string>
     */
    public const STATUSES = ['belum_mulai', 'on_track', 'terlambat', 'selesai'];

    /**
     * @var array<int, string>
     */
    public const PERIODE = ['Triwulan 1', 'Triwulan 2', 'Triwulan 3', 'Triwulan 4'];

    /**
     * Field yang boleh diedit lewat aksi "Perbarui" (lihat ProjectProgressController::update()).
     * tahun_anggaran/periode_laporan sengaja tidak termasuk — keduanya identitas laporan,
     * mengubahnya berarti membuat laporan baru, bukan memperbarui yang sudah ada.
     *
     * @var array<int, string>
     */
    public const FIELD_DAPAT_DIPERBARUI = ['pagu', 'realisasi_anggaran', 'progres_fisik_persen', 'status', 'catatan'];

    public const SUMBER_MANUAL = 'manual';

    public const SUMBER_INAPROC = 'inaproc';

    protected $fillable = [
        'data_spatial_id',
        'opd_id',
        'kategori_id',
        'tahun_anggaran',
        'periode_laporan',
        'pagu',
        'realisasi_anggaran',
        'progres_fisik_persen',
        'status',
        'sumber_data',
        'catatan',
        'dilaporkan_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tahun_anggaran' => 'integer',
            'pagu' => 'decimal:2',
            'realisasi_anggaran' => 'decimal:2',
            'progres_fisik_persen' => 'decimal:2',
        ];
    }

    public function dataSpatial(): BelongsTo
    {
        return $this->belongsTo(DataSpatial::class, 'data_spatial_id');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'kategori_id');
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilaporkan_oleh');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ProjectProgressReportRevision::class)->latest();
    }
}
