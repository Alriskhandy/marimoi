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
        'feedbackable_id',
        'feedbackable_type'
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Polymorphic relationship - feedback bisa terkait ke berbagai model
     */
    public function feedbackable()
    {
        return $this->morphTo();
    }

    /**
     * Scope untuk filter berdasarkan jenis model
     */
    public function scopeForUsulanMusrenbang($query)
    {
        return $query->where('feedbackable_type', UsulanMusrenbang::class);
    }

    public function scopeForProyekStrategisNasional($query)
    {
        return $query->where('feedbackable_type', ProyekStrategisNasional::class);
    }

    public function scopeForProyekStrategisDaerah($query)
    {
        return $query->where('feedbackable_type', ProyekStrategisDaerah::class);
    }

    public function scopeForPokirDprd($query)
    {
        return $query->where('feedbackable_type', PokirDprd::class);
    }

    public function scopeForLokasi($query)
    {
        return $query->where('feedbackable_type', Lokasi::class);
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
     * Helper methods untuk mengecek jenis project yang terkait
     */
    public function isUsulanMusrenbang()
    {
        return $this->feedbackable_type === UsulanMusrenbang::class;
    }

    public function isProyekStrategisNasional()
    {
        return $this->feedbackable_type === ProyekStrategisNasional::class;
    }

    public function isProyekStrategisDaerah()
    {
        return $this->feedbackable_type === ProyekStrategisDaerah::class;
    }

    public function isPokirDprd()
    {
        return $this->feedbackable_type === PokirDprd::class;
    }

    public function isLokasi()
    {
        return $this->feedbackable_type === Lokasi::class;
    }

    /**
     * Get model type name for display
     */
    public function getModelTypeNameAttribute()
    {
        $names = [
            UsulanMusrenbang::class => 'Usulan Musrenbang',
            ProyekStrategisNasional::class => 'Proyek Strategis Nasional',
            ProyekStrategisDaerah::class => 'Proyek Strategis Daerah',
            PokirDprd::class => 'Pokir DPRD',
            Lokasi::class => 'Lokasi',
        ];

        return $names[$this->feedbackable_type] ?? 'Unknown';
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
            'model_types' => [
                'usulan_musrenbang' => self::forUsulanMusrenbang()->count(),
                'proyek_strategis_nasional' => self::forProyekStrategisNasional()->count(),
                'proyek_strategis_daerah' => self::forProyekStrategisDaerah()->count(),
                'pokir_dprd' => self::forPokirDprd()->count(),
                'lokasi' => self::forLokasi()->count(),
            ]
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

// ========================================
// UPDATE MODEL YANG SUDAH ADA
// ========================================

// app/Models/UsulanMusrenbang.php - Tambahkan method ini
class UsulanMusrenbang extends Model
{
    // existing code...

    /**
     * Polymorphic relationship - usulan bisa punya banyak feedback
     */
    public function feedbacks()
    {
        return $this->morphMany(ProjectFeedback::class, 'feedbackable');
    }
}

// app/Models/ProyekStrategisNasional.php - Tambahkan method ini
class ProyekStrategisNasional extends Model
{
    // existing code...

    /**
     * Polymorphic relationship - proyek nasional bisa punya banyak feedback
     */
    public function feedbacks()
    {
        return $this->morphMany(ProjectFeedback::class, 'feedbackable');
    }
}

// app/Models/ProyekStrategisDaerah.php - Tambahkan method ini
class ProyekStrategisDaerah extends Model
{
    // existing code...

    /**
     * Polymorphic relationship - proyek daerah bisa punya banyak feedback
     */
    public function feedbacks()
    {
        return $this->morphMany(ProjectFeedback::class, 'feedbackable');
    }
}

// app/Models/PokirDprd.php - Tambahkan method ini
class PokirDprd extends Model
{
    // existing code...

    /**
     * Polymorphic relationship - pokir bisa punya banyak feedback
     */
    public function feedbacks()
    {
        return $this->morphMany(ProjectFeedback::class, 'feedbackable');
    }
}

// app/Models/Lokasi.php - Tambahkan method ini
class Lokasi extends Model
{
    // existing code...

    /**
     * Polymorphic relationship - lokasi bisa punya banyak feedback
     */
    public function feedbacks()
    {
        return $this->morphMany(ProjectFeedback::class, 'feedbackable');
    }
}

// ========================================
// TESTING COMMANDS
// ========================================

/*
Untuk testing model relationships di Tinker:

php artisan tinker

// Test polymorphic relationship
>>> $feedback = ProjectFeedback::first()
>>> $feedback->feedbackable
>>> $feedback->model_type_name

// Test reverse relationship
>>> $pokir = PokirDprd::first()
>>> $pokir->feedbacks

// Test scopes
>>> ProjectFeedback::forPokirDprd()->count()
>>> ProjectFeedback::pending()->count()
>>> ProjectFeedback::keluhan()->count()

// Test statistics
>>> ProjectFeedback::getStatistics()
>>> ProjectFeedback::getRegionStatistics()

// Test helper methods
>>> $feedback = ProjectFeedback::first()
>>> $feedback->hasCoordinates()
>>> $feedback->google_maps_url
>>> $feedback->image_url
>>> $feedback->hasResponse()
*/