<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

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

    #[OA\Get(
        path: '/api/v1/locations/reference',
        tags: ['Locations'],
        summary: 'Data referensi lokasi Maluku Utara',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
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

    #[OA\Get(
        path: '/api/v1/locations/kecamatan/{kab}',
        tags: ['Locations'],
        summary: 'List kecamatan untuk kabupaten/kota',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'kab', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'Kabupaten tidak valid'),
        ]
    )]
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
                'message'   => 'Data kecamatan belum tersedia di model. Tambahkan konstanta KECAMATAN_BY_KABUPATEN di App\Models\ProjectFeedback bila diperlukan.',
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/locations/maps-center',
        tags: ['Locations'],
        summary: 'Koordinat center peta Maluku Utara',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function getMapsCenter(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'center'                 => ['latitude' => 0.7881, 'longitude' => 127.3781],
                'zoom_level'             => 8,
                'bounds'                 => ['north' => 2.5, 'south' => -2.5, 'east' => 129.0, 'west' => 124.0],
                'kabupaten_coordinates'  => [
                    'Ternate'          => ['lat' => 0.7881,  'lng' => 127.3781],
                    'Tidore Kepulauan' => ['lat' => 0.6781,  'lng' => 127.4020],
                    'Halmahera Barat'  => ['lat' => 1.0147,  'lng' => 127.7334],
                    'Halmahera Timur'  => ['lat' => 1.4853,  'lng' => 127.8492],
                    'Halmahera Utara'  => ['lat' => 1.7281,  'lng' => 128.0139],
                    'Halmahera Selatan'=> ['lat' => -0.9500, 'lng' => 127.4833],
                    'Kepulauan Sula'   => ['lat' => -1.9833, 'lng' => 125.9667],
                    'Halmahera Tengah' => ['lat' => -0.2167, 'lng' => 127.8833],
                    'Pulau Morotai'    => ['lat' => 2.3167,  'lng' => 128.4167],
                    'Pulau Taliabu'    => ['lat' => -1.8333, 'lng' => 124.7833],
                ],
            ],
        ]);
    }
}
