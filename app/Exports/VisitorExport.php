<?php

// Create this file: app/Exports/VisitorExport.php

namespace App\Exports;

use App\Models\Visitor;
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

class VisitorExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize
{
    use Exportable;

    protected $period;

    public function __construct($period = 'today')
    {
        $this->period = $period;
    }

    public function query()
    {
        $query = Visitor::query()->orderBy('created_at', 'desc');

        // Apply period filter
        switch ($this->period) {
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
            case 'year':
                $query->whereYear('created_at', Carbon::now()->year);
                break;
            default:
                $query->today();
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'No',
            'IP Address',
            'Negara',
            'Kota',
            'Halaman Dikunjungi',
            'Browser',
            'Device Type',
            'Operating System',
            'Latitude',
            'Longitude',
            'User Agent',
            'Waktu Kunjungan'
        ];
    }

    public function map($visitor): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $visitor->ip,
            $visitor->country ?: '-',
            $visitor->city ?: '-',
            $visitor->page_visited,
            $visitor->browser,
            $visitor->device_type,
            $visitor->operating_system,
            $visitor->latitude ?: '-',
            $visitor->longitude ?: '-',
            $visitor->user_agent ?: '-',
            Carbon::parse($visitor->created_at)->format('d/m/Y H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '17a2b8']
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
        $sheet->getStyle('A1:L' . $highestRow)->applyFromArray([
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
                $sheet->getStyle('A' . $i . ':L' . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8F9FA']
                    ]
                ]);
            }
        }

        // Text wrapping for long content columns
        $sheet->getStyle('E:E')->getAlignment()->setWrapText(true); // Page visited
        $sheet->getStyle('K:K')->getAlignment()->setWrapText(true); // User agent

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 15,  // IP
            'C' => 15,  // Country
            'D' => 15,  // City
            'E' => 40,  // Page
            'F' => 12,  // Browser
            'G' => 12,  // Device
            'H' => 15,  // OS
            'I' => 12,  // Latitude
            'J' => 12,  // Longitude
            'K' => 50,  // User Agent
            'L' => 18   // Time
        ];
    }

    public function title(): string
    {
        return 'Data Pengunjung - ' . ucfirst($this->period);
    }
}