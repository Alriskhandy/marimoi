<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Template dokumen hasil unduhan publik (Unduh Peta & cetak Analisis Peta):
 * kop/header (logo kiri-kanan + 3 baris teks), footer, dan warna aksen.
 * Hanya template aktif yang tampil di Peta Interaktif; satu template bisa jadi bawaan.
 */
class DocumentTemplate extends Model
{
    use HasFactory;

    /** Slot logo yang bisa diunggah, dipetakan ke kolom path-nya. */
    public const LOGO_SLOTS = ['left' => 'header_logo_left', 'right' => 'header_logo_right'];

    public const LOGO_DIRECTORY = 'document-templates';

    public const ORIENTATIONS = ['portrait' => 'Potret', 'landscape' => 'Lanskap'];

    /**
     * Elemen tata letak Unduh Peta. Posisi & ukuran dalam persen area isi halaman
     * (di antara kop/judul dan footer). Peta wajib ada; elemen lain boleh dinonaktifkan.
     *
     * @var array<string, array{label: string, required: bool}>
     */
    public const LAYOUT_ELEMENTS = [
        'map' => ['label' => 'Peta', 'required' => true],
        'legend' => ['label' => 'Legenda', 'required' => false],
        'inset' => ['label' => 'Inset (peta lokasi)', 'required' => false],
        'scale' => ['label' => 'Skala', 'required' => false],
        'north' => ['label' => 'Arah mata angin', 'required' => false],
    ];

    /** Ukuran minimum elemen (persen) agar tetap terbaca. */
    public const LAYOUT_MIN_SIZE = 4;

    protected $fillable = [
        'name',
        'description',
        'header_enabled',
        'header_logo_left',
        'header_logo_right',
        'header_line1',
        'header_line2',
        'header_line3',
        'accent_color',
        'orientation',
        'layout',
        'footer_text',
        'for_map',
        'for_analysis',
        'is_active',
        'is_default',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'header_enabled' => 'boolean',
            'for_map' => 'boolean',
            'for_analysis' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'layout' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // File logo ikut dihapus bersama template-nya.
        static::deleted(function (DocumentTemplate $template) {
            foreach (self::LOGO_SLOTS as $column) {
                if ($template->{$column}) {
                    Storage::disk('public')->delete($template->{$column});
                }
            }
        });
    }

    /**
     * @param  Builder<DocumentTemplate>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<DocumentTemplate>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderByDesc('is_default')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Tata letak bawaan per orientasi.
     *
     * @return array<string, array{enabled: bool, x: float, y: float, w: float, h: float}>
     */
    public static function defaultLayout(string $orientation): array
    {
        $boxes = $orientation === 'portrait'
            ? [
                'map' => [0, 0, 100, 74],
                'legend' => [0, 76, 64, 24],
                'inset' => [66, 76, 34, 24],
                'scale' => [2, 66, 32, 6],
                'north' => [89, 2, 9, 8],
            ]
            : [
                'map' => [0, 0, 74, 100],
                'legend' => [76, 0, 24, 70],
                'inset' => [76, 72, 24, 28],
                'scale' => [1.5, 89, 22, 9],
                'north' => [66, 3, 6.5, 13],
            ];

        return collect($boxes)->map(fn (array $box) => [
            'enabled' => true,
            'x' => (float) $box[0],
            'y' => (float) $box[1],
            'w' => (float) $box[2],
            'h' => (float) $box[3],
        ])->all();
    }

    /**
     * Rapikan tata letak kiriman formulir: hanya elemen dikenal, angka dibatasi ke halaman,
     * peta selalu aktif; elemen yang tidak dikirim memakai posisi bawaan.
     *
     * @param  array<string, mixed>|null  $layout
     * @return array<string, array{enabled: bool, x: float, y: float, w: float, h: float}>
     */
    public static function normalizeLayout(?array $layout, string $orientation): array
    {
        $defaults = static::defaultLayout($orientation);
        $result = [];

        foreach (self::LAYOUT_ELEMENTS as $key => $definition) {
            $box = is_array($layout[$key] ?? null) ? $layout[$key] : $defaults[$key];
            $w = min(100, max(self::LAYOUT_MIN_SIZE, (float) ($box['w'] ?? $defaults[$key]['w'])));
            $h = min(100, max(self::LAYOUT_MIN_SIZE, (float) ($box['h'] ?? $defaults[$key]['h'])));
            $result[$key] = [
                'enabled' => $definition['required'] || filter_var($box['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'x' => round(min(100 - $w, max(0, (float) ($box['x'] ?? 0))), 2),
                'y' => round(min(100 - $h, max(0, (float) ($box['y'] ?? 0))), 2),
                'w' => round($w, 2),
                'h' => round($h, 2),
            ];
        }

        return $result;
    }

    /**
     * Tata letak yang berlaku (tersimpan, atau bawaan orientasinya).
     *
     * @return array<string, array{enabled: bool, x: float, y: float, w: float, h: float}>
     */
    public function resolvedLayout(): array
    {
        return static::normalizeLayout($this->layout, $this->orientation ?: 'landscape');
    }

    /**
     * Jadikan template ini satu-satunya bawaan.
     */
    public function makeDefault(): void
    {
        DB::transaction(function () {
            static::query()->whereKeyNot($this->getKey())->where('is_default', true)->update(['is_default' => false]);
            $this->forceFill(['is_default' => true])->save();
        });
    }

    /**
     * URL logo lewat rute aplikasi (bukan /storage) agar selalu satu origin dengan
     * halaman peta — canvas Unduh Peta tidak boleh "tainted" oleh gambar lintas origin.
     */
    public function logoUrl(string $slot): ?string
    {
        $column = self::LOGO_SLOTS[$slot] ?? null;

        if (! $column || ! $this->{$column}) {
            return null;
        }

        return route('document-templates.logo', ['documentTemplate' => $this->id, 'slot' => $slot, 'v' => $this->updated_at?->timestamp]);
    }

    /**
     * Data yang dikirim ke Peta Interaktif.
     *
     * @return array{id: int, name: string, description: ?string, isDefault: bool, forMap: bool, forAnalysis: bool, accentColor: string, orientation: string, layout: array<string, array<string, mixed>>, header: ?array{logoLeft: ?string, logoRight: ?string, line1: ?string, line2: ?string, line3: ?string}, footer: array{text: ?string}}
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'isDefault' => $this->is_default,
            'forMap' => $this->for_map,
            'forAnalysis' => $this->for_analysis,
            'accentColor' => $this->accent_color ?: '#1d3557',
            'orientation' => $this->orientation ?: 'landscape',
            'layout' => $this->resolvedLayout(),
            'header' => $this->header_enabled ? [
                'logoLeft' => $this->logoUrl('left'),
                'logoRight' => $this->logoUrl('right'),
                // Baris 1 & 2 selalu kapital; baris 3 sesuai isian dan boleh beberapa baris.
                'line1' => filled($this->header_line1) ? Str::upper(trim($this->header_line1)) : null,
                'line2' => filled($this->header_line2) ? Str::upper(trim($this->header_line2)) : null,
                'line3' => filled($this->header_line3) ? trim(str_replace(["\r\n", "\r"], "\n", $this->header_line3)) : null,
            ] : null,
            'footer' => [
                'text' => $this->footer_text,
            ],
        ];
    }

    /**
     * Template aktif untuk tampilan publik, bawaan lebih dulu.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function publicList(): array
    {
        return static::query()->active()->ordered()->get()->map->toPublicArray()->all();
    }
}
