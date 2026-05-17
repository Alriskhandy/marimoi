<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PublicLocationController extends Controller
{
    private const PROVINSI = 'Maluku Utara';

    private const KABUPATEN_KOTA = [
        'Ternate',
        'Tidore Kepulauan',
        'Halmahera Barat',
        'Halmahera Timur',
        'Halmahera Utara',
        'Halmahera Selatan',
        'Halmahera Tengah',
        'Kepulauan Sula',
        'Pulau Morotai',
        'Pulau Taliabu',
    ];

    public function getReferenceData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'provinsi'       => self::PROVINSI,
                'kabupaten_kota' => self::KABUPATEN_KOTA,
            ],
        ]);
    }

    public function getKecamatan(string $kab): JsonResponse
    {
        if (!in_array($kab, self::KABUPATEN_KOTA, true)) {
            return response()->json([
                'success'         => false,
                'message'         => 'Kabupaten tidak valid',
                'valid_kabupaten' => self::KABUPATEN_KOTA,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'kabupaten' => $kab,
                'kecamatan' => [],
            ],
        ]);
    }

    public function getMapsCenter(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'center'     => ['latitude' => 0.7881, 'longitude' => 127.3781],
                'zoom_level' => 8,
                'bounds'     => ['north' => 2.5, 'south' => -2.5, 'east' => 129.0, 'west' => 124.0],
            ],
        ]);
    }
}
