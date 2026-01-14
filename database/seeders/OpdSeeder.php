<?php

namespace Database\Seeders;

use App\Models\Opd;
use Illuminate\Database\Seeder;

class OpdSeeder extends Seeder
{
    public function run(): void
    {
        $opds = [
            [
                'name' => 'Dinas Komunikasi dan Informatika',
                'singkatan' => 'DISKOMINFO',
                'telepon' => '(021) 1234-5678',
                'email' => 'diskominfo@jakarta.go.id',
            ],
            [
                'name' => 'Dinas Pekerjaan Umum dan Penataan Ruang',
                'singkatan' => 'DPUPR',
                'telepon' => '(021) 2345-6789',
                'email' => 'dpupr@jakarta.go.id',
            ],
            [
                'name' => 'Dinas Kesehatan',
                'singkatan' => 'DINKES',
                'telepon' => '(021) 3456-7890',
                'email' => 'dinkes@jakarta.go.id',
            ],
            [
                'name' => 'Dinas Pendidikan',
                'singkatan' => 'DISDIK',
                'telepon' => '(021) 4567-8901',
                'email' => 'disdik@jakarta.go.id',
            ],
            [
                'name' => 'Dinas Sosial',
                'singkatan' => 'DINSOS',
                'telepon' => '(021) 5678-9012',
                'email' => 'dinsos@jakarta.go.id',
            ]
        ];

        foreach ($opds as $opd) {
            Opd::create($opd);
        }
    }
}