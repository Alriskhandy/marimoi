<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\KategoriLayer;
use App\Models\Lokasi;

class WebGISController extends Controller
{
    /**
     * Show WebGIS page
     */
    public function index()
    {
        return view('webgis');
    }
    
    /**
     * Get GeoJSON data for map
     */
    public function getGeoJson()
    {
        try {
            // Fetch locations with category relationship
            $lokasis = Lokasi::with(['kategoriLayer.parent'])->get();
            
            $features = [];
            foreach ($lokasis as $lokasi) {
                try {
                    // Get geometry as GeoJSON from PostGIS
                    $geojson = $this->getGeometryAsGeoJson($lokasi);
                    
                    if (!$geojson) {
                        Log::warning("Failed to convert geometry for lokasi ID: " . $lokasi->id);
                        continue;
                    }
                    
                    $kategoriData = $lokasi->kategoriLayer;
                    $fullPath = $kategoriData ? $kategoriData->full_path : 'Tidak Dikategorikan';
                    
                    // Get attributes
                    $attributes = $lokasi->parsed_attributes ?? [];
                    
                    $features[] = [
                        'type' => 'Feature',
                        'properties' => array_merge($attributes, [
                            'id' => $lokasi->id,
                            'nama' => $lokasi->nama,
                            'deskripsi' => $lokasi->deskripsi,
                            'kategori' => $kategoriData->nama ?? 'Tidak Dikategorikan',
                            'kategori_full_path' => $fullPath,
                            'kategori_color' => $kategoriData->warna ?? '#gray',
                            'parent_kategori' => $kategoriData->parent->nama ?? null,
                            'created_at' => $lokasi->created_at->format('Y-m-d H:i:s'),
                            'updated_at' => $lokasi->updated_at->format('Y-m-d H:i:s'),
                        ]),
                        'geometry' => $geojson
                    ];
                    
                } catch (\Exception $e) {
                    Log::error("Error processing lokasi ID {$lokasi->id}: " . $e->getMessage());
                    continue;
                }
            }
            
            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features,
                'meta' => [
                    'total_features' => count($features),
                    'total_categories' => KategoriLayer::count(),
                    'generated_at' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error in getGeoJson: " . $e->getMessage());
            return response()->json([
                'error' => 'Gagal memuat data GeoJSON',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get hierarchical categories data
     */
    public function getKategorisHierarchical()
    {
        try {
            $rootCategories = KategoriLayer::whereNull('parent_id')
                ->with(['children' => function($query) {
                    $query->orderBy('nama');
                }])
                ->orderBy('nama')
                ->get();
            
            $allCategories = KategoriLayer::with('parent')->orderBy('nama')->get();
            
            return response()->json([
                'root_categories' => $rootCategories,
                'all_categories' => $allCategories,
                'meta' => [
                    'total_root_categories' => $rootCategories->count(),
                    'total_categories' => $allCategories->count(),
                    'generated_at' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error in getKategorisHierarchical: " . $e->getMessage());
            return response()->json([
                'error' => 'Gagal memuat data kategori',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get categories statistics
     */
    public function getCategoriesStats()
    {
        try {
            $stats = DB::table('kategori_layers as k')
                ->leftJoin('lokasis as l', 'k.id', '=', 'l.kategori_id')
                ->select(
                    'k.id',
                    'k.nama',
                    'k.warna',
                    'k.parent_id',
                    DB::raw('COUNT(l.id) as total_locations')
                )
                ->groupBy('k.id', 'k.nama', 'k.warna', 'k.parent_id')
                ->orderBy('k.nama')
                ->get();
            
            return response()->json([
                'categories_stats' => $stats,
                'meta' => [
                    'total_categories' => $stats->count(),
                    'total_locations' => $stats->sum('total_locations'),
                    'generated_at' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error in getCategoriesStats: " . $e->getMessage());
            return response()->json([
                'error' => 'Gagal memuat statistik kategori',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Search locations
     */
    public function searchLocations(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $categoryId = $request->get('category_id');
            $limit = min($request->get('limit', 50), 100);
            
            $lokasisQuery = Lokasi::with(['kategoriLayer.parent']);
            
            if ($query) {
                $lokasisQuery->where(function($q) use ($query) {
                    $q->where('deskripsi', 'ILIKE', "%{$query}%")
                      ->orWhereRaw("dbf_attributes::text ILIKE ?", ["%{$query}%"]);
                });
            }
            
            if ($categoryId) {
                $lokasisQuery->where('kategori_id', $categoryId);
            }
            
            $lokasis = $lokasisQuery->limit($limit)->get();
            
            $results = $lokasis->map(function($lokasi) {
                $kategoriData = $lokasi->kategoriLayer;
                return [
                    'id' => $lokasi->id,
                    'nama' => $lokasi->nama,
                    'deskripsi' => $lokasi->deskripsi,
                    'kategori' => $kategoriData->nama ?? 'Tidak Dikategorikan',
                    'kategori_full_path' => $kategoriData ? $kategoriData->full_path : 'Tidak Dikategorikan',
                    'kategori_color' => $kategoriData->warna ?? '#gray',
                ];
            });
            
            return response()->json([
                'results' => $results,
                'meta' => [
                    'query' => $query,
                    'total_results' => $results->count(),
                    'category_filter' => $categoryId,
                    'generated_at' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error in searchLocations: " . $e->getMessage());
            return response()->json([
                'error' => 'Gagal melakukan pencarian',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get location detail by ID
     */
    public function getLocationDetail($id)
    {
        try {
            $lokasi = Lokasi::with(['kategoriLayer.parent'])->find($id);
            
            if (!$lokasi) {
                return response()->json([
                    'error' => 'Lokasi tidak ditemukan'
                ], 404);
            }
            
            $geojson = $this->getGeometryAsGeoJson($lokasi);
            $kategoriData = $lokasi->kategoriLayer;
            
            // Get attributes
            $attributes = $lokasi->parsed_attributes ?? [];
            
            return response()->json([
                'type' => 'Feature',
                'properties' => array_merge($attributes, [
                    'id' => $lokasi->id,
                    'nama' => $lokasi->nama,
                    'deskripsi' => $lokasi->deskripsi,
                    'kategori' => $kategoriData->nama ?? 'Tidak Dikategorikan',
                    'kategori_full_path' => $kategoriData ? $kategoriData->full_path : 'Tidak Dikategorikan',
                    'kategori_color' => $kategoriData->warna ?? '#gray',
                    'parent_kategori' => $kategoriData->parent->nama ?? null,
                    'created_at' => $lokasi->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $lokasi->updated_at->format('Y-m-d H:i:s'),
                ]),
                'geometry' => $geojson,
                'meta' => [
                    'generated_at' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error in getLocationDetail: " . $e->getMessage());
            return response()->json([
                'error' => 'Gagal memuat detail lokasi',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Export data in various formats
     */
    public function exportData(Request $request)
    {
        try {
            $format = $request->get('format', 'geojson');
            $categoryIds = $request->get('categories', []);
            
            $query = Lokasi::with(['kategoriLayer.parent']);
            
            if (!empty($categoryIds)) {
                $query->whereIn('kategori_id', $categoryIds);
            }
            
            $lokasis = $query->get();
            
            switch ($format) {
                case 'csv':
                    return $this->exportToCsv($lokasis);
                case 'kml':
                    return $this->exportToKml($lokasis);
                case 'geojson':
                default:
                    return $this->exportToGeoJson($lokasis);
            }
            
        } catch (\Exception $e) {
            Log::error("Error in exportData: " . $e->getMessage());
            return response()->json([
                'error' => 'Gagal mengekspor data',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get geometry as GeoJSON from PostGIS
     */
    private function getGeometryAsGeoJson($lokasi)
    {
        try {
            if (empty($lokasi->geom)) {
                return null;
            }
            
            $result = DB::selectOne(
                "SELECT ST_AsGeoJSON(geom) as geojson FROM lokasis WHERE id = ?", 
                [$lokasi->id]
            );
            
            if ($result && $result->geojson) {
                return json_decode($result->geojson, true);
            }
            
        } catch (\Exception $e) {
            Log::error("Error converting geometry to GeoJSON: " . $e->getMessage());
            return null;
        }
        
        return null;
    }
    
    /**
     * Export to GeoJSON format
     */
    private function exportToGeoJson($lokasis)
    {
        $features = [];
        
        foreach ($lokasis as $lokasi) {
            $geojson = $this->getGeometryAsGeoJson($lokasi);
            if (!$geojson) continue;
            
            $kategoriData = $lokasi->kategoriLayer;
            $attributes = $lokasi->parsed_attributes ?? [];
            
            $features[] = [
                'type' => 'Feature',
                'properties' => array_merge($attributes, [
                    'id' => $lokasi->id,
                    'nama' => $lokasi->nama,
                    'deskripsi' => $lokasi->deskripsi,
                    'kategori' => $kategoriData->nama ?? 'Tidak Dikategorikan',
                    'kategori_full_path' => $kategoriData ? $kategoriData->full_path : 'Tidak Dikategorikan',
                ]),
                'geometry' => $geojson
            ];
        }
        
        $geoJsonData = [
            'type' => 'FeatureCollection',
            'features' => $features,
            'meta' => [
                'exported_at' => now()->toISOString(),
                'total_features' => count($features),
                'export_format' => 'geojson'
            ]
        ];
        
        return response()->json($geoJsonData)
            ->header('Content-Disposition', 'attachment; filename="webgis-export-' . date('Y-m-d-H-i-s') . '.geojson"');
    }
    
    /**
     * Export to CSV format
     */
    private function exportToCsv($lokasis)
    {
        $csvData = [];
        $headers = ['ID', 'Nama', 'Deskripsi', 'Kategori', 'Kategori Full Path', 'Koordinat', 'Attributes', 'Created At', 'Updated At'];
        
        foreach ($lokasis as $lokasi) {
            $kategoriData = $lokasi->kategoriLayer;
            $geojson = $this->getGeometryAsGeoJson($lokasi);
            
            $coordinates = '';
            if ($geojson && isset($geojson['coordinates'])) {
                $coordinates = json_encode($geojson['coordinates']);
            }
            
            $attributes = '';
            if ($lokasi->dbf_attributes) {
                $attributes = is_string($lokasi->dbf_attributes) 
                    ? $lokasi->dbf_attributes 
                    : json_encode($lokasi->dbf_attributes);
            }
            
            $csvData[] = [
                $lokasi->id,
                $lokasi->nama,
                $lokasi->deskripsi,
                $kategoriData->nama ?? 'Tidak Dikategorikan',
                $kategoriData ? $kategoriData->full_path : 'Tidak Dikategorikan',
                $coordinates,
                $attributes,
                $lokasi->created_at->format('Y-m-d H:i:s'),
                $lokasi->updated_at->format('Y-m-d H:i:s'),
            ];
        }
        
        $filename = 'webgis-export-' . date('Y-m-d-H-i-s') . '.csv';
        
        return response()->streamDownload(function() use ($headers, $csvData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
    
    /**
     * Export to KML format
     */
    private function exportToKml($lokasis)
    {
        $kml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $kml .= '<kml xmlns="http://www.opengis.net/kml/2.2">' . PHP_EOL;
        $kml .= '<Document>' . PHP_EOL;
        $kml .= '<name>WebGIS Export</name>' . PHP_EOL;
        $kml .= '<description>Exported from WebGIS on ' . now()->format('Y-m-d H:i:s') . '</description>' . PHP_EOL;
        
        foreach ($lokasis as $lokasi) {
            $geojson = $this->getGeometryAsGeoJson($lokasi);
            if (!$geojson) continue;
            
            $kategoriData = $lokasi->kategoriLayer;
            
            $kml .= '<Placemark>' . PHP_EOL;
            $kml .= '<name>' . htmlspecialchars($lokasi->nama) . '</name>' . PHP_EOL;
            $kml .= '<description>' . htmlspecialchars($kategoriData->nama ?? 'Tidak Dikategorikan') . '</description>' . PHP_EOL;
            
            // Convert GeoJSON coordinates to KML format
            if ($geojson['type'] === 'Point' && isset($geojson['coordinates'])) {
                $coords = $geojson['coordinates'];
                $kml .= '<Point>' . PHP_EOL;
                $kml .= '<coordinates>' . $coords[0] . ',' . $coords[1] . ',0</coordinates>' . PHP_EOL;
                $kml .= '</Point>' . PHP_EOL;
            }
            
            $kml .= '</Placemark>' . PHP_EOL;
        }
        
        $kml .= '</Document>' . PHP_EOL;
        $kml .= '</kml>' . PHP_EOL;
        
        $filename = 'webgis-export-' . date('Y-m-d-H-i-s') . '.kml';
        
        return response($kml, 200, [
            'Content-Type' => 'application/vnd.google-earth.kml+xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}