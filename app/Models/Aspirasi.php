<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Aspirasi extends Model
{
    use HasFactory;
protected $table = 'aspirasi';
    protected $fillable = [
        'kategori_aspirasi_id',
        'nomor_tiket',
        'nama_pengirim',
        'email',
        'phone',
        'alamat',
        'jenis_aspirasi',
        'judul_aspirasi',
        'isi_aspirasi',
        'lampiran',
        'latitude',
        'longitude',
        'tanggapan_admin',
        'status',
        'prioritas',
        'tanggal_respon',
        'admin_id'
    ];

    protected $casts = [
        'lampiran' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'tanggal_respon' => 'datetime',
    ];

    // Boot method for auto-generating ticket number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($aspirasi) {
            if (empty($aspirasi->nomor_tiket)) {
                $aspirasi->nomor_tiket = $aspirasi->generateTicketNumber();
            }
        });
    }

    // Relationships
    public function kategoriAspirasi()
    {
        return $this->belongsTo(KategoriAspirasi::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDiproses($query)
    {
        return $query->where('status', 'diproses');
    }

    public function scopeSelesai($query)
    {
        return $query->where('status', 'selesai');
    }

    public function scopeByPrioritas($query, $prioritas)
    {
        return $query->where('prioritas', $prioritas);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'diproses' => 'info',
            'selesai' => 'success',
            'ditolak' => 'danger',
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    // Methods
    private function generateTicketNumber(): string
{
    $date = Carbon::now()->format('Ymd');

    $lastTicket = static::whereDate('created_at', Carbon::today())
                        ->orderBy('id', 'desc')
                        ->first();

    $sequence = $lastTicket
        ? (int) substr($lastTicket->nomor_tiket, -4) + 1
        : 1;

    $sequenceFormatted = str_pad($sequence, 4, '0', STR_PAD_LEFT);

    return 'MARIMOI-ASP-' . $date . '-' . $sequenceFormatted;
}


    public function updateStatus($status, $adminId = null, $tanggapan = null)
    {
        $this->update([
            'status' => $status,
            'admin_id' => $adminId,
            'tanggapan_admin' => $tanggapan,
            'tanggal_respon' => now()
        ]);
    }
}