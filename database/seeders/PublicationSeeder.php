<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PublicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('publications')->insert([
            [
                'title' => 'Panduan Pembangunan Berkelanjutan',
                'description' => 'Dokumen panduan untuk pembangunan berkelanjutan di wilayah perkotaan.',
                'file_name' => 'panduan_pembangunan_berkelanjutan.pdf',
                'file_path' => 'publications/panduan_pembangunan_berkelanjutan.pdf',
                'file_type' => 'application/pdf',
                'file_size' => 2048000, // in bytes
            ],
        ]);
    }
}
