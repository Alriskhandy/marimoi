<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Opd extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'opd';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'singkatan',
        'logo',
        'telepon',
        'email',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [];

    /**
     * Get the attributes that should be appended to the model's array form.
     */
    protected $appends = [
        'logo_url',
        'has_contact',
    ];

    /**
     * Scope untuk mencari OPD berdasarkan nama atau singkatan
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
              ->orWhere('singkatan', 'like', '%' . $search . '%');
        });
    }

    /**
     * Scope untuk OPD yang memiliki logo
     */
    public function scopeHasLogo($query)
    {
        return $query->whereNotNull('logo');
    }

    /**
     * Scope untuk OPD yang memiliki email
     */
    public function scopeHasEmail($query)
    {
        return $query->whereNotNull('email');
    }

    /**
     * Scope untuk OPD yang memiliki telepon
     */
    public function scopeHasTelepon($query)
    {
        return $query->whereNotNull('telepon');
    }

    /**
     * Scope untuk OPD yang memiliki kontak (email atau telepon)
     */
    public function scopeHasContact($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('email')->orWhereNotNull('telepon');
        });
    }

    /**
     * Get the logo URL attribute
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        return null;
    }

    /**
     * Get the has contact attribute
     */
    public function getHasContactAttribute()
    {
        return !empty($this->email) || !empty($this->telepon);
    }

    /**
     * Get the display name attribute (untuk dropdown atau select)
     */
    public function getDisplayNameAttribute()
    {
        return $this->singkatan . ' - ' . $this->name;
    }

    /**
     * Mutator untuk singkatan (otomatis uppercase)
     */
    public function setSingkatanAttribute($value)
    {
        $this->attributes['singkatan'] = strtoupper($value);
    }

    /**
     * Mutator untuk email (otomatis lowercase)
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = $value ? strtolower($value) : null;
    }

    /**
     * Relasi dengan KategoriAspirasi (jika ada)
     * Uncomment jika diperlukan
     */
    public function kategoriAspirasi()
    {
        return $this->hasMany(KategoriAspirasi::class, 'opd_id');
    }

    /**
     * Relasi dengan Aspirasi (jika ada)
     * Uncomment jika diperlukan
     */
    public function aspirasi()
    {
        return $this->hasManyThrough(Aspirasi::class, KategoriAspirasi::class, 'opd_id', 'kategori_aspirasi_id');
    }

    /**
     * Boot method untuk model events
     */
    protected static function boot()
    {
        parent::boot();

        // Event ketika OPD akan dihapus
        static::deleting(function ($opd) {
            // Hapus logo jika ada
            if ($opd->logo && Storage::disk('public')->exists($opd->logo)) {
                Storage::disk('public')->delete($opd->logo);
            }
        });

        // Event setelah OPD dibuat
        static::created(function ($opd) {
            // Log atau aksi lain setelah OPD dibuat
            Log::info('OPD baru ditambahkan: ' . $opd->name . ' (' . $opd->singkatan . ')');
        });

        // Event setelah OPD diupdate
        static::updated(function ($opd) {
            // Log atau aksi lain setelah OPD diupdate
            Log::info('OPD diperbarui: ' . $opd->name . ' (' . $opd->singkatan . ')');
        });
    }

    /**
     * Method untuk mendapatkan statistik OPD
     */
    public static function getStats()
    {
        return [
            'total' => self::count(),
            'dengan_logo' => self::hasLogo()->count(),
            'dengan_email' => self::hasEmail()->count(),
            'dengan_telepon' => self::hasTelepon()->count(),
            'dengan_kontak' => self::hasContact()->count(),
            'tanpa_kontak' => self::whereNull('email')->whereNull('telepon')->count(),
        ];
    }

    /**
     * Method untuk format telepon
     */
    public function getFormattedTeleponAttribute()
    {
        if (!$this->telepon) {
            return null;
        }

        // Format telepon sederhana
        $telepon = preg_replace('/[^0-9]/', '', $this->telepon);
        
        // Jika dimulai dengan 0, ganti dengan +62
        if (substr($telepon, 0, 1) === '0') {
            $telepon = '+62' . substr($telepon, 1);
        }

        return $telepon;
    }

    /**
     * Check apakah OPD dapat dihapus
     */
    public function canBeDeleted()
    {
        // Cek apakah OPD masih digunakan di tabel lain
        // return !$this->kategoriAspirasi()->exists();
        return true; // Sementara return true, sesuaikan dengan kebutuhan
    }

    /**
     * Method untuk export data
     */
    public function toExportArray()
    {
        return [
            'ID' => $this->id,
            'Nama OPD' => $this->name,
            'Singkatan' => $this->singkatan,
            'Telepon' => $this->telepon ?? '-',
            'Email' => $this->email ?? '-',
            'Logo' => $this->logo ? 'Ada' : 'Tidak Ada',
            'Dibuat Pada' => $this->created_at->format('d/m/Y H:i'),
            'Diubah Pada' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }
}