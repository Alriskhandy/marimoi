<?php

namespace App\Http\Controllers;

use App\Models\KategoriLayer;
use Illuminate\Http\Request;
use App\Models\Lokasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
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
                $possibleDescFields = ['deskripsi', 'desk', 'description', 'desc', 'keterangan', 'ket', 'remark','NAMOBJ'];
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
// dd($request->all());
        // Validasi request
        $request->validate([
            'kategori' => 'required|exists:kategori_layers,id',
            'deskripsi' => 'nullable|string',
            'dbf_attributes' => 'required|string',
            'geom' => 'required|string'
        ]);
 $lokasi = Lokasi::findOrFail($id);
//   dd($lokasi);
        try {
            // Parse dan validate DBF attributes JSON
            $dbfAttributesJson = $request->dbf_attributes;
            $dbfAttributes = json_decode($dbfAttributesJson, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Format JSON atribut DBF tidak valid: ' . json_last_error_msg());
            }

            // Bersihkan dan normalisasi data DBF (sama seperti di store method)
            $cleanDbfData = [];
            foreach ($dbfAttributes as $key => $value) {
                // Bersihkan nama kolom dan nilai
                $cleanKey = trim($key);
                $cleanValue = is_string($value) ? trim($value) : $value;
                
                // Konversi encoding jika diperlukan
                if (is_string($cleanValue) && !mb_check_encoding($cleanValue, 'UTF-8')) {
                    $cleanValue = mb_convert_encoding($cleanValue, 'UTF-8', 'auto');
                }
                
                $cleanDbfData[$cleanKey] = $cleanValue;
            }

            // Validate dan process geometri
            $geometry = trim($request->geom);
            if (empty($geometry)) {
                throw new \Exception('Geometri tidak boleh kosong');
            }
            
            // Basic validation
            $this->validateGeometry($geometry);
            
            // Proses geometri untuk mengatasi masalah dimensi Z dan M (sama seperti store method)
            $processedWkt = $this->processGeometryDimensions($geometry);

            // Log untuk debugging
            Log::info('Updating lokasi', [
                'id' => $lokasi->id,
                'kategori' => $request->kategori,
                'deskripsi' => $request->deskripsi,
                'dbf_count' => count($cleanDbfData),
                'wkt_length' => strlen($processedWkt)
            ]);

            // Update menggunakan DB transaction untuk konsistensi
            DB::beginTransaction();

            try {
                // Update field by field
                $lokasi->kategori_id = $request->kategori;
                $lokasi->deskripsi = $request->deskripsi;
                $lokasi->dbf_attributes = $cleanDbfData;
                $lokasi->updated_at = now();
                
                // Save first without geometry
                $lokasi->save();
                
                // Then update geometry separately using raw SQL
                DB::statement("UPDATE lokasis SET geom = ST_GeomFromText(?, 4326) WHERE id = ?", [
                    $processedWkt, 
                    $lokasi->id
                ]);

                DB::commit();

                Log::info("Lokasi updated successfully. ID: {$lokasi->id}");

                return redirect()->route('lokasi.index')
                    ->with('success', 'Lokasi berhasil diperbarui!');

            } catch (\Exception $e) {
                DB::rollback();
                Log::error('Database update failed: ' . $e->getMessage());
                throw new \Exception('Gagal update database: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('Update lokasi failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal update lokasi: ' . $e->getMessage())
                ->withInput();
        }
    }

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