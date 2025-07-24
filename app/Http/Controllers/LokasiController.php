<?php

namespace App\Http\Controllers;

use App\Models\KategoriLayer;
use Illuminate\Http\Request;
use App\Models\Lokasi;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Shapefile\ShapefileReader;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class LokasiController extends Controller
{
    
    public function index()
    {
        $lokasis = Lokasi::all();
        $kategoriLayers = KategoriLayer::with('children')->whereNull('parent_id')->get();
        return view('backend.pages.data-spasial.data_spasial', compact('lokasis','kategoriLayers'));
    }

   public function edit($id)
    {
        $lokasi = Lokasi::findOrFail($id);
        $kategoriLayers = KategoriLayer::orderBy('nama')->get();
        return view('backend.pages.data-spasial.edit', compact('lokasi', 'kategoriLayers'));
    }

    
    public function create()
    {
         $kategoriLayers = KategoriLayer::with('children')->whereNull('parent_id')->get();
        return view('backend.pages.data-spasial.input-gis', compact('kategoriLayers'));
    }

    public function store(Request $request)
    {
        // Validasi dasar
        $request->validate([
            'kategori_id' => 'required|exists:kategori_layers,id',
            'deskripsi' => 'nullable|string',
            'input_type' => 'required|in:shapefile,coordinates,kmz',
        ]);

        try {
            DB::beginTransaction();

            $recordCount = 0;
            $inputType = $request->input('input_type');

            switch ($inputType) {
                case 'shapefile':
                    $recordCount = $this->processShapefileInput($request);
                    break;
                case 'coordinates':
                    $recordCount = $this->processCoordinatesInput($request);
                    break;
                case 'kmz':
                    $recordCount = $this->processKmzInput($request);
                    break;
                default:
                    throw new \Exception('Jenis input tidak valid');
            }

            DB::commit();

            $message = "Berhasil menyimpan {$recordCount} record dengan metode {$inputType}.";
            return redirect()->route('lokasi.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error storing location data: ' . $e->getMessage());
            return back()->withErrors(['Gagal menyimpan data: ' . $e->getMessage()])->withInput();
        }
    }

    private function processShapefileInput(Request $request)
    {
        $request->validate([
            'shp_file' => 'required|file',
            'shx_file' => 'required|file',
            'dbf_file' => 'required|file',
        ]);

        $folder = storage_path('app/shapefiles');
        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }
        File::cleanDirectory($folder);

        // Simpan file
        $request->file('shp_file')->move($folder, 'data.shp');
        $request->file('shx_file')->move($folder, 'data.shx');
        $request->file('dbf_file')->move($folder, 'data.dbf');

        $shpPath = "$folder/data.shp";

        if (!file_exists($shpPath)) {
            throw new \Exception('Gagal menyimpan file shapefile.');
        }

        $reader = new ShapefileReader($shpPath);
        $recordCount = 0;

        while ($geometry = $reader->fetchRecord()) {
            if ($geometry->isDeleted()) continue;

            $wkt = $geometry->getWKT();
            $dbfData = $geometry->getDataArray();

            // Bersihkan dan normalisasi data DBF
            $cleanDbfData = $this->cleanDbfData($dbfData);

            // Tentukan deskripsi
            $description = $this->determineDescription($request->deskripsi, $cleanDbfData);

            // Proses geometri
            $processedWkt = $this->processGeometryDimensions($wkt);
            $this->validateGeometryCoordinates($processedWkt);

            // Simpan lokasi
            $this->saveLokasi($request->kategori_id, $description, $cleanDbfData, $processedWkt);
            $recordCount++;
        }

        if ($recordCount === 0) {
            throw new \Exception('Shapefile tidak berisi data geometrik yang valid.');
        }

        return $recordCount;
    }

    private function processCoordinatesInput(Request $request)
    {
        $request->validate([
            'coordinates' => 'required|array|min:1',
            'coordinates.*.latitude' => 'required|numeric',
            'coordinates.*.longitude' => 'required|numeric',
            'coordinates.*.name' => 'nullable|string|max:255',
        ]);

        $coordinates = $request->input('coordinates');
        $recordCount = 0;

        foreach ($coordinates as $index => $coord) {
            if (empty($coord['latitude']) || empty($coord['longitude'])) {
                continue;
            }

            $lat = (float) $coord['latitude'];
            $lng = (float) $coord['longitude'];
            $name = $coord['name'] ?? "Lokasi " . ($index + 1);

            // Buat WKT Point
            $wkt = "POINT({$lng} {$lat})";

            // Buat DBF attributes dari input koordinat
            $dbfAttributes = [
                'NAMA' => $name,
                'LATITUDE' => $lat,
                'LONGITUDE' => $lng,
                'INPUT_TYPE' => 'manual_coordinates'
            ];

            // Tentukan deskripsi
            $description = $request->deskripsi ?: $name;

            // Simpan lokasi
            $this->saveLokasi($request->kategori_id, $description, $dbfAttributes, $wkt);
            $recordCount++;
        }

        if ($recordCount === 0) {
            throw new \Exception('Tidak ada koordinat valid yang dapat disimpan.');
        }

        return $recordCount;
    }

    private function processKmzInput(Request $request)
    {
        $request->validate([
            'kmz_file' => 'required|file',
        ]);

        $file = $request->file('kmz_file');
        $fileName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        $tempDir = storage_path('app/temp_kmz');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        File::cleanDirectory($tempDir);

        $kmlContent = null;

        if ($extension === 'kmz') {
            // Extract KMZ file
            $kmzPath = $tempDir . '/temp.kmz';
            $file->move($tempDir, 'temp.kmz');

            $zip = new ZipArchive;
            if ($zip->open($kmzPath) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if (pathinfo($filename, PATHINFO_EXTENSION) === 'kml') {
                        $kmlContent = $zip->getFromIndex($i);
                        break;
                    }
                }
                $zip->close();
            } else {
                throw new \Exception('Gagal membuka file KMZ.');
            }
        } else {
            // Direct KML file
            $kmlContent = file_get_contents($file->getRealPath());
        }

        if (!$kmlContent) {
            throw new \Exception('Tidak dapat menemukan file KML dalam arsip.');
        }

        return $this->parseKmlContent($kmlContent, $request);
    }

    private function parseKmlContent($kmlContent, $request)
    {
        $dom = new DOMDocument();
        $dom->loadXML($kmlContent);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('kml', 'http://www.opengis.net/kml/2.2');

        $recordCount = 0;
        $placemarks = $xpath->query('//kml:Placemark');

        foreach ($placemarks as $placemark) {
            $name = $xpath->query('.//kml:name', $placemark)->item(0);
            $description = $xpath->query('.//kml:description', $placemark)->item(0);
            
            $nameText = $name ? trim($name->textContent) : 'Unnamed';
            $descText = $description ? trim($description->textContent) : '';

            // Parse geometri
            $geometries = $this->parseKmlGeometry($xpath, $placemark);

            foreach ($geometries as $geometry) {
                $dbfAttributes = [
                    'NAMA' => $nameText,
                    'DESCRIPTION' => $descText,
                    'INPUT_TYPE' => 'kmz_import',
                    'ORIGINAL_FILE' => $request->file('kmz_file')->getClientOriginalName()
                ];

                $finalDescription = $request->deskripsi ?: $nameText;

                $this->saveLokasi($request->kategori_id, $finalDescription, $dbfAttributes, $geometry);
                $recordCount++;
            }
        }

        if ($recordCount === 0) {
            throw new \Exception('File KMZ/KML tidak berisi data geometrik yang valid.');
        }

        return $recordCount;
    }

    private function parseKmlGeometry($xpath, $placemark)
    {
        $geometries = [];

        // Parse Point
        $points = $xpath->query('.//kml:Point/kml:coordinates', $placemark);
        foreach ($points as $point) {
            $coords = trim($point->textContent);
            $coordArray = explode(',', $coords);
            if (count($coordArray) >= 2) {
                $lng = trim($coordArray[0]);
                $lat = trim($coordArray[1]);
                if (is_numeric($lng) && is_numeric($lat)) {
                    $geometries[] = "POINT({$lng} {$lat})";
                }
            }
        }

        // Parse LineString
        $lineStrings = $xpath->query('.//kml:LineString/kml:coordinates', $placemark);
        foreach ($lineStrings as $lineString) {
            $coords = trim($lineString->textContent);
            $wkt = $this->convertKmlCoordsToLineString($coords);
            if ($wkt) {
                $geometries[] = $wkt;
            }
        }

        // Parse Polygon
        $polygons = $xpath->query('.//kml:Polygon', $placemark);
        foreach ($polygons as $polygon) {
            $outerBoundary = $xpath->query('.//kml:outerBoundaryIs/kml:LinearRing/kml:coordinates', $polygon)->item(0);
            if ($outerBoundary) {
                $coords = trim($outerBoundary->textContent);
                $wkt = $this->convertKmlCoordsToPolygon($coords);
                if ($wkt) {
                    $geometries[] = $wkt;
                }
            }
        }

        return $geometries;
    }

    private function convertKmlCoordsToLineString($coordsText)
    {
        $points = preg_split('/\s+/', trim($coordsText));
        $wktPoints = [];

        foreach ($points as $point) {
            $coords = explode(',', $point);
            if (count($coords) >= 2) {
                $lng = trim($coords[0]);
                $lat = trim($coords[1]);
                if (is_numeric($lng) && is_numeric($lat)) {
                    $wktPoints[] = "{$lng} {$lat}";
                }
            }
        }

        if (count($wktPoints) >= 2) {
            return "LINESTRING(" . implode(',', $wktPoints) . ")";
        }

        return null;
    }

    private function convertKmlCoordsToPolygon($coordsText)
    {
        $points = preg_split('/\s+/', trim($coordsText));
        $wktPoints = [];

        foreach ($points as $point) {
            $coords = explode(',', $point);
            if (count($coords) >= 2) {
                $lng = trim($coords[0]);
                $lat = trim($coords[1]);
                if (is_numeric($lng) && is_numeric($lat)) {
                    $wktPoints[] = "{$lng} {$lat}";
                }
            }
        }

        if (count($wktPoints) >= 4) {
            // Pastikan polygon tertutup
            if ($wktPoints[0] !== $wktPoints[count($wktPoints) - 1]) {
                $wktPoints[] = $wktPoints[0];
            }
            return "POLYGON((" . implode(',', $wktPoints) . "))";
        }

        return null;
    }

    private function cleanDbfData($dbfData)
    {
        $cleanDbfData = [];
        foreach ($dbfData as $key => $value) {
            $cleanKey = trim($key);
            $cleanValue = is_string($value) ? trim($value) : $value;
            
            if (is_string($cleanValue) && !mb_check_encoding($cleanValue, 'UTF-8')) {
                $cleanValue = mb_convert_encoding($cleanValue, 'UTF-8', 'auto');
            }
            
            $cleanDbfData[$cleanKey] = $cleanValue;
        }
        return $cleanDbfData;
    }

    private function determineDescription($requestDescription, $dbfData)
    {
        if ($requestDescription) {
            return $requestDescription;
        }

        $possibleDescFields = ['NAMA_OBJEK', 'NAMOBJ', 'NAMA', 'NAME'];
        foreach ($possibleDescFields as $field) {
            if (isset($dbfData[$field]) && !empty($dbfData[$field])) {
                return $dbfData[$field];
            }
        }

        return 'Lokasi tanpa nama';
    }

    private function validateGeometryCoordinates($wkt)
    {
        if (preg_match('/POINT\s*\(([\d\.\-]+)\s+([\d\.\-]+)\)/i', $wkt, $matches)) {
            $lng = (float) $matches[1];
            $lat = (float) $matches[2];

            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                throw new \Exception("Koordinat POINT berada di luar jangkauan WGS 84: ({$lng}, {$lat})");
            }
        }
    }

    private function saveLokasi($kategoriId, $description, $dbfAttributes, $wkt)
    {
        $lokasi = new Lokasi();
        $lokasi->kategori_id = $kategoriId;
        $lokasi->deskripsi = $description;
        $lokasi->dbf_attributes = $dbfAttributes;
        $lokasi->geom = DB::raw("ST_Transform(ST_SetSRID(ST_GeomFromText('{$wkt}'), 4326), 4326)");
        $lokasi->save();
    }

    // Debug methods untuk preview
    public function debugShapefile(Request $request)
    {
        $request->validate([
            'shp_file' => 'required|file',
            'shx_file' => 'required|file',
            'dbf_file' => 'required|file',
        ]);

        $folder = storage_path('app/shapefiles');
        if (!file_exists($folder)) mkdir($folder, 0755, true);
        File::cleanDirectory($folder);

        $request->file('shp_file')->move($folder, 'data.shp');
        $request->file('shx_file')->move($folder, 'data.shx');
        $request->file('dbf_file')->move($folder, 'data.dbf');

        $shpPath = "$folder/data.shp";

        try {
            $reader = new ShapefileReader($shpPath);
            $data = [];
            $dbfColumns = [];
            $i = 0;

            while ($feature = $reader->fetchRecord()) {
                if (++$i > 5) break;
                if ($feature->isDeleted()) continue;

                $dbfData = $feature->getDataArray();
                
                if (empty($dbfColumns)) {
                    $dbfColumns = array_keys($dbfData);
                }

                $data[] = [
                    'geometry' => $feature->getWKT(),
                    'properties' => $dbfData,
                ];
            }

            return response()->json([
                'success' => true,
                'sample' => $data,
                'dbf_columns' => $dbfColumns,
                'total_columns' => count($dbfColumns)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function debugKmz(Request $request)
    {
        $request->validate([
            'kmz_file' => 'required|file|mimes:kmz,kml',
        ]);

        try {
            $file = $request->file('kmz_file');
            $extension = $file->getClientOriginalExtension();
            
            $tempDir = storage_path('app/temp_kmz');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            File::cleanDirectory($tempDir);

            $kmlContent = null;

            if ($extension === 'kmz') {
                $kmzPath = $tempDir . '/temp.kmz';
                $file->move($tempDir, 'temp.kmz');

                $zip = new ZipArchive;
                if ($zip->open($kmzPath) === TRUE) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if (pathinfo($filename, PATHINFO_EXTENSION) === 'kml') {
                            $kmlContent = $zip->getFromIndex($i);
                            break;
                        }
                    }
                    $zip->close();
                }
            } else {
                $kmlContent = file_get_contents($file->getRealPath());
            }

            if (!$kmlContent) {
                throw new \Exception('Tidak dapat menemukan file KML dalam arsip.');
            }

            $dom = new DOMDocument();
            $dom->loadXML($kmlContent);
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('kml', 'http://www.opengis.net/kml/2.2');

            $features = [];
            $placemarks = $xpath->query('//kml:Placemark');

            foreach ($placemarks as $index => $placemark) {
                if ($index >= 10) break; // Limit preview

                $name = $xpath->query('.//kml:name', $placemark)->item(0);
                $description = $xpath->query('.//kml:description', $placemark)->item(0);
                
                $nameText = $name ? trim($name->textContent) : 'Unnamed';
                $descText = $description ? trim($description->textContent) : '';

                // Detect geometry type
                $geometryType = 'Unknown';
                if ($xpath->query('.//kml:Point', $placemark)->length > 0) {
                    $geometryType = 'Point';
                } elseif ($xpath->query('.//kml:LineString', $placemark)->length > 0) {
                    $geometryType = 'LineString';
                } elseif ($xpath->query('.//kml:Polygon', $placemark)->length > 0) {
                    $geometryType = 'Polygon';
                }

                $features[] = [
                    'name' => $nameText,
                    'description' => $descText,
                    'geometry_type' => $geometryType
                ];
            }

            return response()->json([
                'success' => true,
                'features' => $features,
                'total_features' => $placemarks->length
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // Existing methods (peta, geojson, etc.) tetap sama seperti sebelumnya...
    
    public function peta()
    {
        return view('backend.pages.peta');
    }

   public function geojson(Request $request)
{
   $query = DB::table('lokasis')
        ->join('kategori_layers', 'lokasis.kategori_id', '=', 'kategori_layers.id')
       ->select(
    'lokasis.id',
    'lokasis.kategori_id',
    'kategori_layers.nama as kategori',
    'lokasis.deskripsi',
    'lokasis.dbf_attributes',
    'kategori_layers.icon',
    'kategori_layers.warna',
    'kategori_layers.is_marker',
    DB::raw('ST_AsGeoJSON(lokasis.geom) as geojson')
)
;

    // Filter kategori
    if ($request->has('kategori') && !empty($request->kategori)) {
        $categories = is_array($request->kategori) ? $request->kategori : [$request->kategori];
        $query->whereIn('kategori_layers.nama', $categories);
    }

    // Filter atribut DBF
    if ($request->has('dbf_filter') && !empty($request->dbf_filter)) {
        foreach ($request->dbf_filter as $attribute => $value) {
            $query->whereRaw("dbf_attributes->? = ?", [$attribute, json_encode($value)]);
        }
    }

    // Search
    if ($request->has('search') && !empty($request->search)) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('kategori_layers.nama', 'ILIKE', "%{$search}%")
              ->orWhere('lokasis.deskripsi', 'ILIKE', "%{$search}%")
              ->orWhereRaw("dbf_attributes::text ILIKE ?", ["%{$search}%"]);
        });
    }

    // BBOX
    if ($request->has('bbox') && !empty($request->bbox)) {
        $bbox = explode(',', $request->bbox);
        if (count($bbox) === 4) {
            $query->whereRaw("ST_Intersects(lokasis.geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))", $bbox);
        }
    }

    $lokasis = $query->get();

    $features = $lokasis->map(function ($lokasi) {
        $dbfAttributes = json_decode($lokasi->dbf_attributes, true) ?? [];

        return [
    'type' => 'Feature',
    'properties' => array_merge([
        'id' => $lokasi->id,
        'kategori_id' => $lokasi->kategori_id,
        'kategori' => $lokasi->kategori,
        'deskripsi' => $lokasi->deskripsi,
        'icon' => $lokasi->icon,
        'warna' => $lokasi->warna,
        'is_marker' => (bool) $lokasi->is_marker,
    ], $dbfAttributes),
    'geometry' => json_decode($lokasi->geojson),
];
    });

    $rootCategories = KategoriLayer::whereNull('parent_id')
                ->with(['children' => function($query) {
                    $query->orderBy('nama');
                }])
                ->orderBy('nama')
                ->get();
            
    $allCategories = KategoriLayer::with('parent')->orderBy('nama')->get();
     
    return response()->json([
        'type' => 'FeatureCollection',
        'features' => $features,
        'root_categories' => $rootCategories,
        'all_categories' => $allCategories,
        'meta' => [
            'total_root_categories' => $rootCategories->count(),
            'total_categories' => $allCategories->count(),
            'generated_at' => now()->toISOString()
        ]
    ]);
}

    // Method untuk mendapatkan kolom DBF yang tersedia (PostgreSQL JSONB)
    public function getDbfColumns()
    {
        $columns = DB::select("
            SELECT DISTINCT jsonb_object_keys(dbf_attributes) as column_name 
            FROM lokasis 
            WHERE dbf_attributes IS NOT NULL
            ORDER BY column_name
        ");

        $columnNames = array_map(function($col) {
            return $col->column_name;
        }, $columns);

        return response()->json([
            'success' => true,
            'columns' => $columnNames
        ]);
    }

    // Method untuk mendapatkan nilai unik dari kolom DBF tertentu (PostgreSQL JSONB)
    public function getDbfColumnValues($column)
    {
        $values = DB::table('lokasis')
            ->whereNotNull('dbf_attributes')
            ->whereRaw("dbf_attributes ? ?", [$column])
            ->pluck(DB::raw("DISTINCT dbf_attributes->>'{$column}'"))
            ->filter(function($value) {
                return !is_null($value) && $value !== '';
            })
            ->values();

        return response()->json([
            'success' => true,
            'column' => $column,
            'values' => $values
        ]);
    }

    // Method baru untuk mendapatkan daftar kategori
    public function getCategories()
{
    $categories = DB::table('lokasis')
        ->join('kategori_layers', 'lokasis.kategori_id', '=', 'kategori_layers.id')
        ->select('kategori_layers.id as kategori_id', 'kategori_layers.nama as kategori', DB::raw('COUNT(*) as count'))
        ->groupBy('kategori_layers.id', 'kategori_layers.nama')
        ->orderBy('kategori_layers.nama')
        ->get();

    return response()->json([
        'success' => true,
        'categories' => $categories
    ]);
}

    // Method baru untuk mendapatkan statistik data
  public function getStatistics()
{
    $stats = [
        'total_locations' => DB::table('lokasis')->count(),
        'categories_count' => DB::table('lokasis')->distinct('kategori_id')->count(),
        'categories' => DB::table('lokasis')
            ->join('kategori_layers', 'lokasis.kategori_id', '=', 'kategori_layers.id')
            ->select('kategori_layers.nama as kategori', DB::raw('COUNT(*) as count'))
            ->groupBy('kategori_layers.nama')
            ->orderBy('count', 'desc')
            ->get(),
        'bounds' => DB::table('lokasis')
            ->select(
                DB::raw('ST_XMin(ST_Extent(geom)) as min_lng'),
                DB::raw('ST_YMin(ST_Extent(geom)) as min_lat'),
                DB::raw('ST_XMax(ST_Extent(geom)) as max_lng'),
                DB::raw('ST_YMax(ST_Extent(geom)) as max_lat')
            )
            ->first()
    ];

    return response()->json([
        'success' => true,
        'statistics' => $stats
    ]);
}

    // Method untuk mendapatkan data berdasarkan kategori tertentu
    public function getByCategory($kategori)
{
    $lokasis = DB::table('lokasis')
        ->join('kategori_layers', 'lokasis.kategori_id', '=', 'kategori_layers.id')
        ->select('lokasis.id', 'lokasis.kategori_id', 'kategori_layers.nama as kategori', 'lokasis.deskripsi', 'lokasis.dbf_attributes', DB::raw('ST_AsGeoJSON(geom) as geojson'))
        ->where('kategori_layers.nama', $kategori)
        ->get();

    $features = $lokasis->map(function ($lokasi) {
        $dbfAttributes = json_decode($lokasi->dbf_attributes, true) ?? [];

        return [
            'type' => 'Feature',
            'properties' => array_merge([
                'id' => $lokasi->id,
                'kategori_id' => $lokasi->kategori_id,
                'kategori' => $lokasi->kategori,
                'deskripsi' => $lokasi->deskripsi,
            ], $dbfAttributes),
            'geometry' => json_decode($lokasi->geojson),
        ];
    });

    return response()->json([
        'type' => 'FeatureCollection',
        'features' => $features,
    ]);
}

public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'kategori' => 'required|exists:kategori_layers,id',
        'deskripsi' => 'nullable|string|max:255',
        'dbf_attributes' => 'nullable|string'
    ], [
        'kategori.required' => 'Kategori harus dipilih',
        'kategori.exists' => 'Kategori tidak valid',
        'deskripsi.max' => 'Deskripsi maksimal 255 karakter'
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    try {
        $lokasi = Lokasi::find($id);
        if (!$lokasi) {
            return redirect()->route('lokasi.index')
                ->with('error', 'Lokasi tidak ditemukan');
        }

        $dbfAttributes = [];
        if ($request->dbf_attributes) {
            $json = $request->dbf_attributes;
            $dbfAttributes = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()
                    ->withErrors(['dbf_attributes' => 'Format JSON atribut tidak valid: ' . json_last_error_msg()])
                    ->withInput();
            }
        }

        $lokasi->kategori_id = $request->kategori;
        $lokasi->deskripsi = $request->deskripsi;
        $lokasi->dbf_attributes = $dbfAttributes; // array, auto-cast to JSONB
        $lokasi->save();

        Log::info('Lokasi updated successfully', [
            'id' => $id,
            'kategori_id' => $request->kategori,
            'attributes_count' => count($dbfAttributes)
        ]);

        return redirect()->route('lokasi.index')
            ->with('success', 'Lokasi berhasil diperbarui');
    } catch (\Exception $e) {
        Log::error('Error updating lokasi: ' . $e->getMessage(), [
            'id' => $id,
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()->back()
            ->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()])
            ->withInput();
    }
}

    public function destroy($id)
    {
        $lokasi = Lokasi::findOrFail($id);
        $lokasi->delete();

        return redirect()->route('lokasi.index')->with('success', 'Data berhasil dihapus.');
    }

    /**
     * Proses geometri untuk mengatasi masalah dimensi Z dan M
     * Mengkonversi MULTIPOLYGONZM ke format yang sesuai dengan kolom database
     */
    private function processGeometryDimensions($wkt)
    {
        try {
            // Jika menggunakan tabel dengan kolom GEOMETRYZM, kembalikan WKT apa adanya
            // Jika menggunakan kolom GEOMETRY biasa, strip dimensi Z dan M
            
            // Deteksi jenis geometri
            if (strpos($wkt, 'ZM') !== false) {
                // Geometri memiliki dimensi Z dan M
                return $wkt; // Kembalikan apa adanya jika kolom mendukung ZM
            } elseif (strpos($wkt, 'Z ') !== false || strpos($wkt, 'M ') !== false) {
                // Geometri memiliki dimensi Z atau M saja
                return $wkt; // Kembalikan apa adanya
            }
            
            // Untuk backward compatibility, jika database tidak mendukung dimensi ZM
            // kita bisa strip dimensi tambahan
            return $this->stripGeometryDimensions($wkt);
            
        } catch (\Exception $e) {
            Log::warning("Gagal memproses geometri: " . $e->getMessage());
            return $this->stripGeometryDimensions($wkt);
        }
    }

    /**
     * Strip dimensi Z dan M dari WKT jika diperlukan
     */
    private function stripGeometryDimensions($wkt)
    {
        // Hapus suffix ZM, Z, atau M dari tipe geometri
        $wkt = preg_replace('/\b(MULTIPOLYGON|POLYGON|MULTIPOINT|POINT|MULTILINESTRING|LINESTRING|GEOMETRYCOLLECTION)(ZM|Z|M)\b/i', '$1', $wkt);
        
        // Hapus koordinat Z dan M (asumsi koordinat dalam format X Y Z M)
        // Pattern untuk mendeteksi koordinat dengan 4 dimensi (X Y Z M)
        $wkt = preg_replace_callback('/(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)/', function($matches) {
            // Kembalikan hanya X Y (koordinat pertama dan kedua)
            return $matches[1] . ' ' . $matches[2];
        }, $wkt);
        
        // Pattern untuk mendeteksi koordinat dengan 3 dimensi (X Y Z)
        $wkt = preg_replace_callback('/(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)(?!\s+\-?\d)/', function($matches) {
            // Kembalikan hanya X Y (koordinat pertama dan kedua)
            return $matches[1] . ' ' . $matches[2];
        }, $wkt);
        
        return $wkt;
    }

    /**
     * Method alternatif untuk mengatasi geometri ZM dengan konversi di PostgreSQL
     */
   private function saveGeometryWithFallback($kategori_id, $deskripsi, $dbfAttributes, $wkt)
{
    try {
        return Lokasi::create([
            'kategori_id' => $kategori_id,
            'deskripsi' => $deskripsi,
            'dbf_attributes' => $dbfAttributes,
            'geom' => DB::raw("ST_GeomFromText('{$wkt}', 4326)"),
        ]);
    } catch (\Exception $e) {
        Log::info("Mencoba konversi geometri: " . $e->getMessage());

        try {
            return Lokasi::create([
                'kategori_id' => $kategori_id,
                'deskripsi' => $deskripsi,
                'dbf_attributes' => $dbfAttributes,
                'geom' => DB::raw("ST_Force2D(ST_GeomFromText('{$wkt}', 4326))"),
            ]);
        } catch (\Exception $e2) {
            Log::error("Gagal konversi geometri: " . $e2->getMessage());

            $strippedWkt = $this->stripGeometryDimensions($wkt);
            return Lokasi::create([
                'kategori_id' => $kategori_id,
                'deskripsi' => $deskripsi,
                'dbf_attributes' => $dbfAttributes,
                'geom' => DB::raw("ST_GeomFromText('{$strippedWkt}', 4326)"),
            ]);
        }
    }
}

}