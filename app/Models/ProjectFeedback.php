<?php

// app/Models/ProjectFeedback.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectFeedback extends Model
{
    use HasFactory;

    protected $table = 'project_feedbacks';
    
    protected $fillable = [
        'data_spatial_id',
        'nama_pemberi_aspirasi',
        'nama_proyek',
        'kabupaten_kota',
        'kecamatan',
        'latitude',
        'longitude',
        'laporan_gambar',
        'tanggapan',
        'jenis_tanggapan',
        'status',
        'email',
        'phone',
        'response_admin',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Relationship ke data_spatial
     */
    public function dataSpatial()
    {
        return $this->belongsTo(DataSpatial::class, 'data_spatial_id');
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDitinjau($query)
    {
        return $query->where('status', 'ditinjau');
    }

    public function scopeDitindaklanjuti($query)
    {
        return $query->where('status', 'ditindaklanjuti');
    }

    public function scopeSelesai($query)
    {
        return $query->where('status', 'selesai');
    }

    /**
     * Scope untuk filter berdasarkan jenis tanggapan
     */
    public function scopeKeluhan($query)
    {
        return $query->where('jenis_tanggapan', 'keluhan');
    }

    public function scopeSaran($query)
    {
        return $query->where('jenis_tanggapan', 'saran');
    }

    public function scopeApresiasi($query)
    {
        return $query->where('jenis_tanggapan', 'apresiasi');
    }

    public function scopePertanyaan($query)
    {
        return $query->where('jenis_tanggapan', 'pertanyaan');
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'pending' => 'badge-warning',
            'ditinjau' => 'badge-info',
            'ditindaklanjuti' => 'badge-primary',
            'selesai' => 'badge-success'
        ];

        return $classes[$this->status] ?? 'badge-secondary';
    }

    /**
     * Get jenis tanggapan badge class
     */
    public function getJenisBadgeClassAttribute()
    {
        $classes = [
            'keluhan' => 'badge-danger',
            'saran' => 'badge-info',
            'apresiasi' => 'badge-success',
            'pertanyaan' => 'badge-warning'
        ];

        return $classes[$this->jenis_tanggapan] ?? 'badge-secondary';
    }

    /**
     * Check if feedback has coordinates
     */
    public function hasCoordinates()
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Get Google Maps URL
     */
    public function getGoogleMapsUrlAttribute()
    {
        if (!$this->hasCoordinates()) {
            return null;
        }

        return "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}";
    }

    /**
     * Check if feedback has admin response
     */
    public function hasResponse()
    {
        return !is_null($this->response_admin) && !is_null($this->responded_at);
    }

    /**
     * Get image URL
     */
    public function getImageUrlAttribute()
    {
        if (!$this->laporan_gambar) {
            return null;
        }

        return asset('storage/feedback_images/' . $this->laporan_gambar);
    }

    /**
     * Static method untuk mendapatkan statistik
     */
    public static function getStatistics()
    {
        return [
            'total' => self::count(),
            'pending' => self::pending()->count(),
            'ditinjau' => self::ditinjau()->count(),
            'ditindaklanjuti' => self::ditindaklanjuti()->count(),
            'selesai' => self::selesai()->count(),
            'keluhan' => self::keluhan()->count(),
            'saran' => self::saran()->count(),
            'apresiasi' => self::apresiasi()->count(),
            'pertanyaan' => self::pertanyaan()->count(),
        ];
    }

    /**
     * Static method untuk mendapatkan statistik berdasarkan region
     */
    public static function getRegionStatistics()
    {
        return self::selectRaw('kabupaten_kota, count(*) as total')
            ->groupBy('kabupaten_kota')
            ->orderBy('total', 'desc')
            ->get()
            ->pluck('total', 'kabupaten_kota')
            ->toArray();
    }
}