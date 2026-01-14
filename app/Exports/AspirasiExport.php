<?php

namespace App\Exports;

use App\Models\Aspirasi;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Carbon;

class AspirasiExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize
{
    use Exportable;

    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Aspirasi::with(['kategoriAspirasi.opd', 'admin'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($this->filters['kategori'])) {
            $query->where('kategori_aspirasi_id', $this->filters['kategori']);
        }

        if (!empty($this->filters['opd'])) {
            $query->whereHas('kategoriAspirasi', function ($q) {
                $q->where('opd_id', $this->filters['opd']);
            });
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['jenis'])) {
            $query->where('jenis_aspirasi', $this->filters['jenis']);
        }

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor Tiket',
            'Nama Pengirim',
            'Email',
            'Telepon',
            'Alamat',
            'Kategori',
            'OPD Terkait',
            'Jenis Aspirasi',
            'Judul Aspirasi',
            'Isi Aspirasi',
            'Status',
            'Tanggal Dibuat',
            'Admin Penanggungjawab',
            'Tanggapan Admin',
            'Tanggal Respon',
            'Koordinat (Lat, Lng)',
            'Jumlah Lampiran'
        ];
    }

    public function map($aspirasi): array
    {
        static $no = 0;
        $no++;

        $koordinat = '';
        if ($aspirasi->latitude && $aspirasi->longitude) {
            $koordinat = $aspirasi->latitude . ', ' . $aspirasi->longitude;
        }

        $jumlahLampiran = 0;
        if ($aspirasi->lampiran) {
            $lampiran = is_string($aspirasi->lampiran) ? json_decode($aspirasi->lampiran, true) : $aspirasi->lampiran;
            $jumlahLampiran = is_array($lampiran) ? count($lampiran) : 0;
        }

        return [
            $no,
            $aspirasi->nomor_tiket,
            $aspirasi->nama_pengirim,
            $aspirasi->email,
            $aspirasi->phone ?: '-',
            $aspirasi->alamat,
            $aspirasi->kategoriAspirasi->nama_kategori ?? 'Tanpa Kategori',
            $aspirasi->kategoriAspirasi->opd->name ?? 'Umum',
            ucfirst($aspirasi->jenis_aspirasi),
            $aspirasi->judul_aspirasi,
            strip_tags($aspirasi->isi_aspirasi),
            ucfirst($aspirasi->status),
            Carbon::parse($aspirasi->created_at)->format('d/m/Y H:i:s'),
            $aspirasi->admin->name ?? '-',
            $aspirasi->tanggapan_admin ?: '-',
            $aspirasi->tanggal_respon ? Carbon::parse($aspirasi->tanggal_respon)->format('d/m/Y H:i:s') : '-',
            $koordinat ?: '-',
            $jumlahLampiran
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Apply borders to all data
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:R' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        // Alternate row colors
        for ($i = 2; $i <= $highestRow; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':R' . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8F9FA']
                    ]
                ]);
            }
        }

        // Text wrapping for long content columns
        $sheet->getStyle('J:K')->getAlignment()->setWrapText(true);
        $sheet->getStyle('O:O')->getAlignment()->setWrapText(true);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 15,  // Nomor Tiket
            'C' => 20,  // Nama Pengirim
            'D' => 25,  // Email
            'E' => 15,  // Telepon
            'F' => 30,  // Alamat
            'G' => 20,  // Kategori
            'H' => 25,  // OPD
            'I' => 15,  // Jenis Aspirasi
            'J' => 30,  // Judul Aspirasi
            'K' => 40,  // Isi Aspirasi
            'L' => 12,  // Status
            'M' => 18,  // Tanggal Dibuat
            'N' => 20,  // Admin
            'O' => 35,  // Tanggapan Admin
            'P' => 18,  // Tanggal Respon
            'Q' => 20,  // Koordinat
            'R' => 15   // Jumlah Lampiran
        ];
    }

    public function title(): string
    {
        return 'Data Aspirasi Masyarakat';
    }
}