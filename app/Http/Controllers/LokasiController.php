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
    $lokasi = Lokasi::findOrFail($id); // atau gunakan model binding jika ingin lebih rapi

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
        // dd($request->all());
        $request->validate([
            'kategori_id' => 'required',
            'deskripsi' => 'nullable',
            'shp_file' => 'required|file',
            'shx_file' => 'required|file',
            'dbf_file' => 'required|file',
        ]);

        $folder = storage_path('app/shapefiles');

        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }

        File::cleanDirectory($folder);

        try {
            // Simpan file
            $request->file('shp_file')->move($folder, 'data.shp');
            $request->file('shx_file')->move($folder, 'data.shx');
            $request->file('dbf_file')->move($folder, 'data.dbf');

            $shpPath = "$folder/data.shp";

            if (!file_exists($shpPath)) {
                return back()->withErrors(['Gagal menyimpan file shapefile.']);
            }

            $reader = new ShapefileReader($shpPath);
            $recordCount = 0;
            $dbfColumns = [];

            while ($geometry = $reader->fetchRecord()) {
                if ($geometry->isDeleted()) continue;

                $wkt = $geometry->getWKT(); // Well-Known Text format
                $dbfData = $geometry->getDataArray(); // Atribut DBF

                // Kumpulkan semua kolom DBF untuk referensi
                if (empty($dbfColumns)) {
                    $dbfColumns = array_keys($dbfData);
                    Log::info('DBF Columns found: ' . implode(', ', $dbfColumns));
                }

                // Bersihkan dan normalisasi data DBF
                $cleanDbfData = [];
                foreach ($dbfData as $key => $value) {
                    // Bersihkan nama kolom dan nilai
                    $cleanKey = trim($key);
                    $cleanValue = is_string($value) ? trim($value) : $value;
                    
                    // Konversi encoding jika diperlukan
                    if (is_string($cleanValue) && !mb_check_encoding($cleanValue, 'UTF-8')) {
                        $cleanValue = mb_convert_encoding($cleanValue, 'UTF-8', 'auto');
                    }
                    
                    $cleanDbfData[$cleanKey] = $cleanValue;
                }

                // Cari deskripsi dari berbagai kemungkinan field DBF
                $possibleDescFields = ['NAMA_OBJEK','NAMOBJ'];
                $description = $request->deskripsi;
                
                if (!$description) {
                    foreach ($possibleDescFields as $field) {
                        if (isset($cleanDbfData[$field]) && !empty($cleanDbfData[$field])) {
                            $description = $cleanDbfData[$field];
                            break;
                        }
                    }
                }

                // Proses geometri untuk mengatasi masalah dimensi Z dan M
                $processedWkt = $this->processGeometryDimensions($wkt);

                // Simpan ke database
                // Lokasi::create([
                //     'kategori_id' => $request->kategori_id,
                //     'deskripsi' => $description,
                //     'dbf_attributes' => $cleanDbfData, // Simpan semua atribut DBF
                //     'geom' => DB::raw("ST_GeomFromText('{$processedWkt}', 4326)"),
                // ]);
                $lokasi = new Lokasi();
                $lokasi->kategori_id = $request->kategori_id;
                $lokasi->deskripsi = $description;
                $lokasi->dbf_attributes = $cleanDbfData;
                $lokasi->geom = DB::raw("ST_GeomFromText('{$processedWkt}', 4326)");
                $lokasi->save();
                // DB::table('lokasis')->insert([
                //     'kategori_id' => $request->kategori_id,
                //     'deskripsi' => $description,
                //     'dbf_attributes' => $cleanDbfData,
                //     'geom' => DB::raw("ST_GeomFromText('{$processedWkt}', 4326)"),
                //     'created_at' => now(),
                //     'updated_at' => now(),
                // ]);

                $recordCount++;
            }

            if ($recordCount === 0) {
                return back()->withErrors(['Shapefile tidak berisi data geometrik yang valid.']);
            }

            $message = "Berhasil menyimpan {$recordCount} record dari shapefile.";
            if (!empty($dbfColumns)) {
                $message .= " Kolom DBF yang tersimpan: " . implode(', ', $dbfColumns);
            }

            return redirect()->route('lokasi.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Gagal membaca shapefile: ' . $e->getMessage());
            return back()->withErrors(['Gagal membaca shapefile: ' . $e->getMessage()]);
        }
    }

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
                
                // Kumpulkan kolom DBF
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
            'kategori_layers.nama as kategori', // ⬅️ ini sangat penting!
            'lokasis.deskripsi',
            'lokasis.dbf_attributes',
            DB::raw('ST_AsGeoJSON(lokasis.geom) as geojson')
        );

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
            ], $dbfAttributes),
            'geometry' => json_decode($lokasi->geojson),
        ];
    });

    return response()->json([
        'type' => 'FeatureCollection',
        'features' => $features,
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


    /**
     * Validate WKT (Well-Known Text) format - Improved
     */
    private function validateWKT($wkt)
    {
        if (empty($wkt)) {
            return false;
        }

        $wkt = trim($wkt);

        // Check if it's WKB (binary format) - starts with hex digits
        if (preg_match('/^[0-9A-Fa-f]+$/', $wkt) && strlen($wkt) > 20) {
            // This is likely WKB format, try to convert it
            return $this->validateWKB($wkt);
        }

        // Normalize whitespace for WKT
        $wkt = preg_replace('/\s+/', ' ', $wkt);

        // Check basic WKT structure
        if (!preg_match('/^(POINT|LINESTRING|POLYGON|MULTIPOINT|MULTILINESTRING|MULTIPOLYGON)\s*\(/i', $wkt)) {
            return false;
        }

        // Get geometry type
        preg_match('/^(\w+)/i', $wkt, $matches);
        $geomType = strtoupper($matches[1]);

        switch ($geomType) {
            case 'POINT':
                return $this->validatePoint($wkt);
            case 'LINESTRING':
                return $this->validateLineString($wkt);
            case 'POLYGON':
                return $this->validatePolygon($wkt);
            case 'MULTIPOINT':
                return $this->validateMultiPoint($wkt);
            case 'MULTILINESTRING':
                return $this->validateMultiLineString($wkt);
            case 'MULTIPOLYGON':
                return $this->validateMultiPolygon($wkt);
            default:
                return false;
        }
    }

    /**
     * Validate WKB (Well-Known Binary) format
     */
    private function validateWKB($wkb)
    {
        // Basic validation for WKB hex string
        if (!preg_match('/^[0-9A-Fa-f]+$/', $wkb)) {
            return false;
        }

        // WKB should have even length (pairs of hex digits)
        if (strlen($wkb) % 2 !== 0) {
            return false;
        }

        // Minimum length check (at least header + type)
        if (strlen($wkb) < 18) {
            return false;
        }

        try {
            // Try to extract basic WKB structure
            $bytes = hex2bin($wkb);
            if ($bytes === false) {
                return false;
            }

            // Check if we have enough bytes for the header
            if (strlen($bytes) < 9) {
                return false;
            }

            // Read endianness (1 byte) and geometry type (4 bytes)
            // This is a basic validation - in production you might want more thorough checking
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Convert WKB to WKT for storage normalization
     */
    private function convertWKBToWKT($wkb)
    {
        // This is a basic converter. For production, consider using PostGIS or similar
        // For now, we'll store WKB as-is but mark it as valid
        
        try {
            // Try to determine geometry type from WKB
            $bytes = hex2bin($wkb);
            if (strlen($bytes) >= 9) {
                // Read geometry type (skip endianness byte)
                $typeBytes = substr($bytes, 1, 4);
                $type = unpack('V', $typeBytes)[1]; // Little endian 32-bit int
                
                // Basic geometry type mapping
                $typeMap = [
                    1 => 'POINT',
                    2 => 'LINESTRING', 
                    3 => 'POLYGON',
                    4 => 'MULTIPOINT',
                    5 => 'MULTILINESTRING',
                    6 => 'MULTIPOLYGON'
                ];
                
                $geomType = $typeMap[$type & 0xFF] ?? 'UNKNOWN';
                
                // For now, return a placeholder WKT indicating the type
                // In production, you'd implement full WKB to WKT conversion
                return "-- WKB DATA: {$geomType} --\n" . $wkb;
            }
        } catch (Exception $e) {
            // If conversion fails, return original
        }
        
        return $wkb;
    }

    private function validatePoint($wkt)
    {
        // POINT(x y) or POINT EMPTY
        if (preg_match('/^POINT\s*EMPTY$/i', $wkt)) {
            return true;
        }
        
        return preg_match('/^POINT\s*\(\s*-?\d+(?:\.\d+)?\s+-?\d+(?:\.\d+)?\s*\)$/i', $wkt);
    }

    private function validateLineString($wkt)
    {
        if (preg_match('/^LINESTRING\s*EMPTY$/i', $wkt)) {
            return true;
        }

        if (!preg_match('/^LINESTRING\s*\((.*)\)$/i', $wkt, $matches)) {
            return false;
        }

        $coords = trim($matches[1]);
        return $this->validateCoordinateString($coords, 2);
    }

    private function validatePolygon($wkt)
    {
        if (preg_match('/^POLYGON\s*EMPTY$/i', $wkt)) {
            return true;
        }

        // Simple polygon validation
        if (!preg_match('/^POLYGON\s*\(\s*\((.*?)\)\s*\)$/i', $wkt, $matches)) {
            return false;
        }

        $coords = trim($matches[1]);
        return $this->validateCoordinateString($coords, 4); // Minimum 4 points for polygon
    }

    private function validateMultiPoint($wkt)
    {
        if (preg_match('/^MULTIPOINT\s*EMPTY$/i', $wkt)) {
            return true;
        }

        return preg_match('/^MULTIPOINT\s*\(\s*(\(\s*-?\d+(?:\.\d+)?\s+-?\d+(?:\.\d+)?\s*\)\s*,?\s*)+\)$/i', $wkt);
    }

    private function validateMultiLineString($wkt)
    {
        if (preg_match('/^MULTILINESTRING\s*EMPTY$/i', $wkt)) {
            return true;
        }

        return preg_match('/^MULTILINESTRING\s*\(\s*(\(\s*(-?\d+(?:\.\d+)?\s+-?\d+(?:\.\d+)?\s*,?\s*)+\)\s*,?\s*)+\)$/i', $wkt);
    }

    private function validateMultiPolygon($wkt)
    {
        if (preg_match('/^MULTIPOLYGON\s*EMPTY$/i', $wkt)) {
            return true;
        }

        return preg_match('/^MULTIPOLYGON\s*\(\s*(\(\s*\(\s*(-?\d+(?:\.\d+)?\s+-?\d+(?:\.\d+)?\s*,?\s*)+\)\s*\)\s*,?\s*)+\)$/i', $wkt);
    }

    /**
     * Validate coordinate string
     */
    private function validateCoordinateString($coords, $minPoints = 1)
    {
        if (empty($coords)) {
            return false;
        }

        // Split by comma to get individual points
        $points = array_map('trim', explode(',', $coords));
        
        if (count($points) < $minPoints) {
            return false;
        }

        // Validate each coordinate pair
        foreach ($points as $point) {
            if (empty($point)) continue;
            
            // Each point should have exactly 2 coordinates (x y)
            $coordPair = preg_split('/\s+/', trim($point));
            
            if (count($coordPair) != 2) {
                return false;
            }

            // Check if both coordinates are valid numbers
            if (!is_numeric($coordPair[0]) || !is_numeric($coordPair[1])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate geometry via AJAX
     */
    


  private function validateGeometry($geometry)
    {
        $geometry = trim($geometry);
        
        if (empty($geometry)) {
            throw new \Exception('Geometri tidak boleh kosong');
        }

        // Enhanced WKT validation patterns
        $wktPatterns = [
            '/^POINT\s*(Z|M|ZM)?\s*\(/i',
            '/^LINESTRING\s*(Z|M|ZM)?\s*\(/i',
            '/^POLYGON\s*(Z|M|ZM)?\s*\(/i',
            '/^MULTIPOINT\s*(Z|M|ZM)?\s*\(/i',
            '/^MULTILINESTRING\s*(Z|M|ZM)?\s*\(/i',
            '/^MULTIPOLYGON\s*(Z|M|ZM)?\s*\(/i',
            '/^GEOMETRYCOLLECTION\s*\(/i'
        ];
        
        $isValid = false;
        foreach ($wktPatterns as $pattern) {
            if (preg_match($pattern, $geometry)) {
                $isValid = true;
                break;
            }
        }
        
        if (!$isValid) {
            throw new \Exception('Format geometri tidak valid. Gunakan format WKT yang benar.');
        }
        
        return true;
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