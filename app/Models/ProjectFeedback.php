<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProjectFeedback extends Model
{
    use HasFactory;

    protected $table = 'project_feedbacks';

    protected $fillable = [
        'nama_proyek',
        'nama_pemberi_aspirasi',
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
        'responded_at'
    ];

    // Konstanta untuk Maluku Utara
    const PROVINSI = 'Maluku Utara';
    
    const KABUPATEN_KOTA = [
        'Ternate',
        'Tidore Kepulauan',
        'Halmahera Barat',
        'Halmahera Timur',
        'Halmahera Utara',
        'Halmahera Selatan',
        'Kepulauan Sula',
        'Halmahera Tengah',
        'Pulau Morotai',
        'Pulau Taliabu'
    ];

    const KECAMATAN_BY_KABUPATEN = [
        'Ternate' => [
            'Ternate Tengah', 'Ternate Utara', 'Ternate Selatan', 
            'Pulau Ternate', 'Moti', 'Pulau Batang Dua'
        ],
        'Tidore Kepulauan' => [
            'Tidore', 'Tidore Timur', 'Tidore Utara', 'Oba',
            'Oba Utara', 'Oba Tengah', 'Oba Selatan'
        ],
        'Halmahera Barat' => [
            'Jailolo', 'Jailolo Selatan', 'Sahu', 'Ibu',
            'Ibu Utara', 'Sahu Timur', 'Loloda'
        ],
        'Halmahera Timur' => [
            'Maba', 'Maba Selatan', 'Wasile', 'Wasile Timur',
            'Wasile Tengah', 'Maba Utara'
        ],
        'Halmahera Utara' => [
            'Tobelo', 'Tobelo Barat', 'Tobelo Timur', 'Galela',
            'Galela Barat', 'Tobelo Tengah', 'Tobelo Selatan',
            'Kao', 'Kao Utara', 'Kao Teluk', 'Malifut', 'Loloda Utara'
        ],
        'Halmahera Selatan' => [
            'Labuha', 'Bacan', 'Bacan Timur', 'Makian',
            'Kayoa', 'Gane Timur', 'Obi Selatan', 'Obi',
            'Bacan Barat', 'Kasiruta Timur', 'Kasiruta Barat',
            'Makian Barat', 'Kayoa Utara'
        ],
        'Kepulauan Sula' => [
            'Sanana', 'Mangole', 'Taliabu', 'Sulabesi',
            'Mangole Utara', 'Mangole Timur', 'Mangole Tengah',
            'Taliabu Timur', 'Taliabu Barat', 'Taliabu Utara',
            'Taliabu Selatan', 'Lisiela', 'Seho'
        ],
        'Halmahera Tengah' => [
            'Weda', 'Patani', 'Patani Barat', 'Gane Barat',
            'Gane Barat Selatan', 'Gane Barat Utara',
            'Weda Utara', 'Weda Tengah', 'Weda Selatan'
        ],
        'Pulau Morotai' => [
            'Morotai Selatan', 'Morotai Timur', 'Morotai Utara',
            'Morotai Jaya', 'Morotai Barat'
        ],
        'Pulau Taliabu' => [
            'Taliabu Timur', 'Taliabu Barat', 'Taliabu Utara',
            'Taliabu Selatan'
        ]
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Accessor untuk koordinat dalam format string
    protected function coordinates(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->latitude && $this->longitude 
                ? "{$this->latitude}, {$this->longitude}" 
                : null,
        );
    }

    // Accessor untuk URL gambar lengkap
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->laporan_gambar 
                ? asset('storage/feedback_images/' . $this->laporan_gambar)
                : null,
        );
    }

    // Scope untuk filter berdasarkan status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope untuk filter berdasarkan jenis tanggapan
    public function scopeByJenisTanggapan($query, $jenis)
    {
        return $query->where('jenis_tanggapan', $jenis);
    }

    // Accessor untuk provinsi (selalu Maluku Utara)
    protected function provinsi(): Attribute
    {
        return Attribute::make(
            get: fn () => self::PROVINSI,
        );
    }

    // Scope untuk filter berdasarkan kabupaten/kota (validasi otomatis)
    public function scopeByKabupatenKota($query, $kabupatenKota)
    {
        if (!in_array($kabupatenKota, self::KABUPATEN_KOTA)) {
            throw new \InvalidArgumentException("Kabupaten/Kota '$kabupatenKota' tidak valid untuk Maluku Utara");
        }
        return $query->where('kabupaten_kota', $kabupatenKota);
    }

    // Method untuk validasi kecamatan berdasarkan kabupaten
    public function validateKecamatan($kecamatan, $kabupaten)
    {
        $validKecamatan = self::KECAMATAN_BY_KABUPATEN[$kabupaten] ?? [];
        return in_array($kecamatan, $validKecamatan);
    }

    // Method untuk mendapatkan semua kecamatan di suatu kabupaten
    public static function getKecamatanByKabupaten($kabupaten)
    {
        return self::KECAMATAN_BY_KABUPATEN[$kabupaten] ?? [];
    }

    // Boot method untuk auto-set provinsi
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            // Validasi kabupaten sebelum create
            if (!in_array($model->kabupaten_kota, self::KABUPATEN_KOTA)) {
                throw new \InvalidArgumentException("Kabupaten/Kota '{$model->kabupaten_kota}' tidak valid untuk Maluku Utara");
            }
            
            // Validasi kecamatan jika ada
            if ($model->kecamatan && !$model->validateKecamatan($model->kecamatan, $model->kabupaten_kota)) {
                throw new \InvalidArgumentException("Kecamatan '{$model->kecamatan}' tidak valid untuk {$model->kabupaten_kota}");
            }
        });
    }

    // Scope untuk filter berdasarkan proyek
    public function scopeByProject($query, $projectName)
    {
        return $query->where('nama_proyek', 'like', "%{$projectName}%");
    }

    // Scope untuk feedback terbaru
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Method untuk menandai tanggapan sebagai selesai
    public function markAsSelesai($adminResponse = null)
    {
        $this->update([
            'status' => 'selesai',
            'response_admin' => $adminResponse,
            'responded_at' => now()
        ]);
    }

    // Method untuk mendapatkan jarak dari koordinat tertentu (dalam km)
    public function getDistanceFrom($latitude, $longitude)
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $earthRadius = 6371; // radius bumi dalam km
        
        $latDiff = deg2rad($this->latitude - $latitude);
        $lonDiff = deg2rad($this->longitude - $longitude);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($latitude)) * cos(deg2rad($this->latitude)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    public function getJenisBadgeClassAttribute()
{
    return match ($this->jenis_tanggapan) {
        'positif' => 'success',
        'negatif' => 'danger',
        'netral'  => 'secondary',
        default   => 'light',
    };
}

public function getStatusBadgeClassAttribute()
{
    return match ($this->status) {
        'diajukan' => 'warning',
        'diproses' => 'primary',
        'diterima' => 'success',
        'ditolak'  => 'danger',
        default    => 'secondary',
    };
}

}