<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DataSpatial;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Shapefile\ShapefileReader;
use Illuminate\Support\Facades\Validator;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class DataSpatialController extends Controller
{

    // === METHODS UMUM ===
    
  public function index(Request $request)
{
    $type = $request->get('type');
    $subType = $request->get('sub_type');

    if (!in_array($type, ['lokasi', 'usulan_musrenbang', 'pokir_dprd', 'proyek_strategis'])) {
       return redirect()->back();
    }

    if ($type === 'proyek_strategis' && !in_array($subType, ['psd', 'psn'])) {
       return redirect()->back(); // sub_type invalid
    }

    // Lanjut proses seperti biasa
    $year = $request->get('year');
    $query = DataSpatial::with(['kategori', 'kategori.parent']);

    $query->where('data_type', $type);

    if ($subType && $type === 'proyek_strategis') {
        $query->where('sub_type', $subType);
    }

    if ($year) {
        $query->where('tahun', $year);
    }

    $data = $query->orderBy('created_at', 'desc')->paginate(20);

    // Ambil kategori
    $categoriesQuery = Category::with('children')->roots();
    if ($type === 'proyek_strategis') {
        $categories = $categoriesQuery->where('type', $subType)->get();
    } else {
        $categoryType = $this->getCategoryTypeByDataType($type, $subType);
        $categories = $categoriesQuery->where('type', $categoryType)->get();
    }

    return view('backend.pages.data_spatial.index', compact(
        'data',
        'categories',
        'type',
        'subType',
        'year'
    ));
}



    public function create(Request $request)
        {
            $dataType = $request->get('type');
            $subType = $request->get('sub_type');
            $year = $request->get('year');
            
            // Tentukan kategori berdasarkan data type dan sub type
            $categoryType = $this->getCategoryTypeByDataType($dataType, $subType);
            
            // Query kategori
            $categoriesQuery = Category::with('children')->roots();
            
            // Logika untuk menentukan filter kategori
            if ($dataType === 'proyek_strategis' && in_array($subType, ['psd', 'psn'])) {
                // Untuk proyek strategis, gunakan sub_type sebagai type
                $categories = $categoriesQuery->where('type', $subType)->get();
            } else {
                // Untuk data type lain, gunakan category type
                if ($subType) {
                    $categories = $categoriesQuery->where('sub_type', $categoryType)->get();
                } else {
                    $categories = $categoriesQuery->where('type', $categoryType)->get();
                }
            }
            
            return view('backend.pages.data_spatial.create', compact(
                'categories', 
                'dataType', 
                'subType', 
                'year'
            ));
        }

    public function store(Request $request)
    {
        // dd($request->all());
        // Validasi dasar
        $rules = [
            'data_type' => 'required|in:lokasi,usulan_musrenbang,pokir_dprd,proyek_strategis',
            'kategori_id' => 'required|exists:categories,id',
            'deskripsi' => 'nullable|string',
            'input_type' => 'required|in:shapefile,coordinates,kmz',
        ];

          // Validasi tambahan untuk proyek strategis
            if ($request->data_type === 'proyek_strategis') {
                $rules['sub_type'] = 'required|in:psn,psd';
                $rules['tahun'] = 'required|integer|min:2000|max:2050';
            }

        $request->validate($rules);

        // Validasi kategori sesuai dengan data type
        $this->validateCategoryType($request);

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
            
            // Redirect berdasarkan data type
            return $this->getRedirectAfterStore($request, $message);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error storing data spatial: ' . $e->getMessage());
            return back()->withErrors(['Gagal menyimpan data: ' . $e->getMessage()])->withInput();
        }
    }
public function edit($uuid)
{
    // Ambil data dengan relasi kategori
   $data = DataSpatial::with(['kategori', 'kategori.parent'])
    ->where('uuid', $uuid)
    ->firstOrFail();

    
    // Ambil data type dan sub type dari data yang ada
    $dataType = $data->data_type;
    $subType = $data->sub_type;
    $year = $data->tahun;
    
    // Tentukan kategori berdasarkan data type dan sub type
    $categoryType = $this->getCategoryTypeByDataType($dataType, $subType);
    
    // Query kategori
    $categoriesQuery = Category::with('children')->roots();
    
    // Logika untuk menentukan filter kategori (sama persis dengan create)
    if ($dataType === 'proyek_strategis' && in_array($subType, ['psd', 'psn'])) {
        // Untuk proyek strategis, gunakan sub_type sebagai type
        $categories = $categoriesQuery->where('type', $subType)->get();
    } else {
        // Untuk data type lain, gunakan category type
        if ($subType) {
            $categories = $categoriesQuery->where('sub_type', $categoryType)->get();
        } else {
            $categories = $categoriesQuery->where('type', $categoryType)->get();
        }
    }
    
    return view('backend.pages.data_spatial.edit', compact(
        'data',
        'categories', 
        'dataType', 
        'subType', 
        'year'
    ));
}


    public function update(Request $request, $id)
{
    // dd($request->kategori_id);
    $validator = Validator::make($request->all(), [
        'kategori_id' => 'required|exists:categories,id',
        'deskripsi' => 'nullable|string|max:255',
        'dbf_attributes' => 'nullable|string',
        'gambar' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048' // Validasi gambar
    ], [
        'kategori_id.required' => 'Kategori harus dipilih',
        'kategori_id.exists' => 'Kategori tidak valid',
        'deskripsi.max' => 'Deskripsi maksimal 255 karakter',
        'gambar.image' => 'File harus berupa gambar',
        'gambar.mimes' => 'Format gambar harus jpeg, jpg, png, atau gif',
        'gambar.max' => 'Ukuran gambar maksimal 2MB'
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    try {
        $data = DataSpatial::find($id);
        if (!$data) {
            return redirect()->route('data-spatial.index')
                ->with('error', 'Data tidak ditemukan');
        }

        // Handle DBF Attributes
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

        // Handle Image Upload
        $imagePath = $data->gambar; // Keep existing image path
        
        if ($request->hasFile('gambar')) {
            // Delete old image if exists
            if ($data->gambar && Storage::disk('public')->exists($data->gambar)) {
                Storage::disk('public')->delete($data->gambar);
            }
            
            // Store new image
            $file = $request->file('gambar');
            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('images/spatial', $fileName, 'public');
        }

        // Update data
        $data->kategori_id = $request->kategori_id;
        $data->deskripsi = $request->deskripsi;
        $data->dbf_attributes = $dbfAttributes;
        $data->gambar = $imagePath;
        $data->save();

        Log::info('Data spatial updated successfully', [
            'id' => $id,
            'data_type' => $data->data_type,
            'kategori_id' => $request->kategori_id,
            'attributes_count' => count($dbfAttributes),
            'image_uploaded' => $request->hasFile('gambar')
        ]);

        return $this->getRedirectAfterUpdate($data)
            ->with('success', 'Data berhasil diperbarui');
            
    } catch (\Exception $e) {
        // If there was an error and a new image was uploaded, delete it
        if ($request->hasFile('gambar') && isset($imagePath) && $imagePath !== $data->gambar) {
            Storage::disk('public')->delete($imagePath);
        }
        
        Log::error('Error updating data spatial: ' . $e->getMessage(), [
            'id' => $id,
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()->back()
            ->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()])
            ->withInput();
    }
}

    public function destroy($uuid)
{
    $data = DataSpatial::where('uuid', $uuid)->firstOrFail();
    $redirectRoute = $this->getRedirectAfterDestroy($data);
    $data->delete();

    return redirect()->route($redirectRoute['route'], $redirectRoute['params'] ?? [])
        ->with('success', 'Data berhasil dihapus.');
}

    // === METHODS KHUSUS BERDASARKAN DATA TYPE ===
    
   public function indexLokasi(Request $request)
{
    if ($request->get('type') !== 'lokasi') {
       return redirect()->back();
    }

    $data = DataSpatial::with('kategori')
        ->where('data_type', 'lokasi')
        ->get();

    $categories = Category::layers()->with('children')->roots()->get();
    
    return view('backend.pages.data_spatial.index', compact('data', 'categories'));
}


    public function indexUsulanMusrenbang(Request $request)
    {
        if ($request->get('type') !== 'usulan_musrenbang') {
           return redirect()->back();
        }

        $data = DataSpatial::with('kategori')
            ->where('data_type', 'usulan_musrenbang')
            ->get();

        $categories = Category::musenbangs()->with('children')->roots()->get();

        return view('backend.pages.data_spatial.index', compact('data', 'categories'));
    }


   public function indexPokirDprd(Request $request)
{
    if ($request->get('type') !== 'pokir_dprd') {
       return redirect()->back();
    }

    $data = DataSpatial::with('kategori')
        ->where('data_type', 'pokir_dprd')
        ->get();

    $categories = Category::pokirDprds()->with('children')->roots()->get();

    return view('backend.pages.data_spatial.index', compact('data', 'categories'));
}


    public function indexProyekStrategisDaerah($year = null)
    {
        // dd($year);
        $query = DataSpatial::where('tahun', $year)
                    ->where('sub_type', 'psd');// Pastikan sub_type konsisten
                  
        
        // if ($year) {
        //     $query->where('tahun', $year);
            
        //     // Validasi apakah tahun tersebut ada dalam database
        //     $yearExists = ProyekStrategisDaerah::where('tahun', $year)->exists();
        //     if (!$yearExists) {
        //         return redirect()->route('psd.index')
        //             ->with('error', "Data untuk tahun {$year} tidak ditemukan.");
        //     }
        // }
        
        $data = $query->get();
        $categories = Category::psd()->with('children')->roots()->get();
        
        // Statistik untuk tahun tersebut jika ada
        $statistics = null;
        if ($year) {
            $statistics = [
                'total' => $data->count(),
                'categories' => $data->groupBy('kategori_id')->map->count(),
                'year' => $year
            ];
        }
        
        return view('backend.pages.data_spatial.index', compact('data', 'categories', 'year', 'statistics'));
    }

    public function indexProyekStrategisNasional($year = null)
    {
        $query = DataSpatial::where('tahun', $year)
                    ->where('sub_type', 'psn');// Pastikan sub_type konsisten
        
        if ($year) {
            $query->where('tahun', $year);
        }
        
        $data = $query->get();
        $categories = Category::psn()->with('children')->roots()->get();
        
        return view('backend.pages.data_spatial.index', compact('data', 'categories', 'year'));
    }

    // === GEOJSON METHODS ===
    
    public function geojson(Request $request)
    {
        $dataType = $request->get('data_type');
        $subType = $request->get('sub_type');
        $year = $request->get('year');

        $query = DB::table('data_spatial')
            ->join('categories', 'data_spatial.kategori_id', '=', 'categories.id')
            ->select(
                'data_spatial.id',
                'data_spatial.data_type',
                'data_spatial.sub_type',
                'data_spatial.kategori_id',
                'data_spatial.tahun',
                'categories.nama as kategori',
                'data_spatial.deskripsi',
                'data_spatial.dbf_attributes',
                'categories.icon',
                'categories.warna',
                'categories.is_marker',
                DB::raw('ST_AsGeoJSON(data_spatial.geom) as geojson')
            );

        // Filter berdasarkan data type
        if ($dataType) {
            $query->where('data_spatial.data_type', $dataType);
        }

        // Filter berdasarkan sub type
        if ($subType) {
            $query->where('data_spatial.sub_type', $subType);
        }

        // Filter berdasarkan tahun
        if ($year) {
            $query->where('data_spatial.tahun', $year);
        }

        // Filter kategori
        if ($request->has('kategori') && !empty($request->kategori)) {
            $categories = is_array($request->kategori) ? $request->kategori : [$request->kategori];
            $query->whereIn('categories.nama', $categories);
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
                $q->where('categories.nama', 'ILIKE', "%{$search}%")
                  ->orWhere('data_spatial.deskripsi', 'ILIKE', "%{$search}%")
                  ->orWhereRaw("dbf_attributes::text ILIKE ?", ["%{$search}%"]);
            });
        }

        // BBOX
        if ($request->has('bbox') && !empty($request->bbox)) {
            $bbox = explode(',', $request->bbox);
            if (count($bbox) === 4) {
                $query->whereRaw("ST_Intersects(data_spatial.geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))", $bbox);
            }
        }

        $data = $query->get();

        $features = $data->map(function ($item) {
            $dbfAttributes = json_decode($item->dbf_attributes, true) ?? [];

            return [
                'type' => 'Feature',
                'properties' => array_merge([
                    'id' => $item->id,
                    'data_type' => $item->data_type,
                    'sub_type' => $item->sub_type,
                    'kategori_id' => $item->kategori_id,
                    'kategori' => $item->kategori,
                    'tahun' => $item->tahun,
                    'deskripsi' => $item->deskripsi,
                    'icon' => $item->icon,
                    'warna' => $item->warna,
                    'is_marker' => (bool) $item->is_marker,
                ], $dbfAttributes),
                'geometry' => json_decode($item->geojson),
            ];
        });

        // Get categories untuk response
        $categoryType = $this->getCategoryTypeByDataType($dataType, $subType);
        $rootCategories = Category::where('type', $categoryType)
                    ->with(['children' => function($query) {
                        $query->orderBy('nama');
                    }])
                    ->roots()
                    ->orderBy('nama')
                    ->get();
                
        $allCategories = Category::where('type', $categoryType)
                       ->with('parent')
                       ->orderBy('nama')
                       ->get();
         
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'root_categories' => $rootCategories,
            'all_categories' => $allCategories,
            'meta' => [
                'data_type' => $dataType,
                'sub_type' => $subType,
                'year' => $year,
                'total_features' => $features->count(),
                'total_root_categories' => $rootCategories->count(),
                'total_categories' => $allCategories->count(),
                'generated_at' => now()->toISOString()
            ]
        ]);
    }

    // === PROCESSING INPUT METHODS ===
    
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

            // Simpan data
            $this->saveDataSpatial($request, $description, $cleanDbfData, $processedWkt);

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
            $name = $coord['name'] ?? $this->getDefaultNameByDataType($request->data_type, $index + 1);

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

            // Simpan data
            $this->saveDataSpatial($request, $description, $dbfAttributes, $wkt);

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

                $this->saveDataSpatial($request, $finalDescription, $dbfAttributes, $geometry);

                $recordCount++;
            }
        }

        if ($recordCount === 0) {
            throw new \Exception('File KMZ/KML tidak berisi data geometrik yang valid.');
        }

        return $recordCount;
    }

    // === HELPER METHODS ===
    
    private function saveDataSpatial(Request $request, $description, $dbfAttributes, $wkt)
    {
        try {
            $data = [
                'data_type' => $request->data_type,
                'kategori_id' => $request->kategori_id,
                'deskripsi' => $description,
                'dbf_attributes' => $dbfAttributes,
                'geom' => DB::raw("ST_Transform(ST_SetSRID(ST_GeomFromText('{$wkt}'), 4326), 4326)")
            ];

            // Tambahkan sub_type dan tahun jika ada
            if ($request->has('sub_type')) {
                $data['sub_type'] = $request->sub_type;
            }

            if ($request->has('tahun')) {
                $data['tahun'] = $request->tahun;
            }

            DataSpatial::create($data);
        } catch (\Exception $e) {
            Log::error('Failed to save DataSpatial: ' . $e->getMessage());
            throw $e;
        }
    }

    private function validateCategoryType(Request $request)
{
    $category = Category::find($request->kategori_id);

    if (!$category) {
        throw new \Exception("Kategori tidak ditemukan.");
    }

    $expectedType = $this->getCategoryTypeByDataType(
        $request->data_type,
        $request->sub_type
    );

    if ($category->type !== $expectedType) {
        throw new \Exception("Kategori harus bertipe '{$expectedType}' untuk data '{$request->data_type}'.");
    }
}

private function getCategoryTypeByDataType($dataType, $subType = null)
{
    return match ($dataType) {
        'lokasi' => 'layers',
        'usulan_musrenbang' => 'musrenbangs',
        'pokir_dprd' => 'pokir_dprds',
       'proyek_strategis' => in_array($subType, ['psn', 'psd']) ? $subType : 'psd',
        default => 'layers',
    };
}


    private function getDefaultNameByDataType($dataType, $index)
    {
        return match($dataType) {
            'lokasi' => "Lokasi {$index}",
            'usulan_musrenbang' => "Usulan Musrenbang {$index}",
            'pokir_dprd' => "Pokir DPRD {$index}",
            'proyek_strategis' => "Proyek Strategis {$index}",
            default => "Data {$index}"
        };
    }

   private function getRedirectAfterStore(Request $request, $message)
{
    if ($request->data_type === 'proyek_strategis') {
        if ($request->has('tahun')) {
            $routeName = $request->sub_type === 'nasional' ? 'psn.tahun.show' : 'psd.tahun.show';
            return redirect()->route($routeName, ['year' => $request->tahun])
                ->with('success', $message);
        }

        $routeName = $request->sub_type === 'nasional' ? 'psn.index' : 'psd.index';
        return redirect()->route($routeName)->with('success', $message);
    }

    // Redirect dinamis ke data-spatial.index dengan query string
    return redirect()->route('data-spatial.index', array_filter([
        'type' => $request->data_type,
        'sub_type' => $request->sub_type ?? null,
    ]))->with('success', $message);
}


    // private function getRedirectAfterUpdate(DataSpatial $data)
    // {
    //     return match($data->data_type) {
    //         'lokasi' => redirect()->route('lokasi.index'),
    //         'usulan_musrenbang' => redirect()->route('usulan-musrenbang.index'),
    //         'pokir_dprd' => redirect()->route('pokir-dprd.index'),
    //         'proyek_strategis' => $data->tahun 
    //             ? redirect()->route($data->sub_type === 'nasional' ? 'psn.tahun.show' : 'psd.tahun.show', ['year' => $data->tahun])
    //             : redirect()->route($data->sub_type === 'nasional' ? 'psn.index' : 'psd.index'),
    //         default => redirect()->route('data-spatial.index')
    //     };
    // }
private function getRedirectAfterUpdate(DataSpatial $data)
{
    if ($data->data_type === 'proyek_strategis') {
        if ($data->tahun) {
            $routeName = $data->sub_type === 'psn' ? 'psn.tahun.show' : 'psd.tahun.show';
            return redirect()->route($routeName, ['year' => $data->tahun]);
        }

        $routeName = $data->sub_type === 'psn' ? 'psn.index' : 'psd.index';
        return redirect()->route($routeName);
    }

    // Redirect dinamis ke data-spatial.index
    return redirect()->route('data-spatial.index', array_filter([
        'type' => $data->data_type,
        'sub_type' => $data->sub_type ?? null,
    ]));
}

    private function getRedirectAfterDestroy(DataSpatial $data)
{
    $baseRoute = 'data-spatial.index';

    $params = ['type' => $data->data_type];

    if ($data->data_type === 'proyek_strategis') {
        $params['sub_type'] = $data->sub_type;

        if ($data->tahun) {
            // Redirect ke route tahun proyek strategis
            $route = $data->sub_type === 'nasional' ? 'psn.tahun.show' : 'psd.tahun.show';
            return ['route' => $route, 'params' => ['year' => $data->tahun]];
        } else {
            // Redirect ke route index proyek strategis
            $route = $data->sub_type === 'nasional' ? 'psn.index' : 'psd.index';
            return ['route' => $route];
        }
    }

    return ['route' => $baseRoute, 'params' => $params];
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

        return 'Data tanpa nama';
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

    private function processGeometryDimensions($wkt)
    {
        try {
            if (strpos($wkt, 'ZM') !== false) {
                return $wkt;
            } elseif (strpos($wkt, 'Z ') !== false || strpos($wkt, 'M ') !== false) {
                return $wkt;
            }
            
            return $this->stripGeometryDimensions($wkt);
            
        } catch (\Exception $e) {
            Log::warning("Gagal memproses geometri: " . $e->getMessage());
            return $this->stripGeometryDimensions($wkt);
        }
    }

    private function stripGeometryDimensions($wkt)
    {
        // Hapus suffix ZM, Z, atau M dari tipe geometri
        $wkt = preg_replace('/\b(MULTIPOLYGON|POLYGON|MULTIPOINT|POINT|MULTILINESTRING|LINESTRING|GEOMETRYCOLLECTION)(ZM|Z|M)\b/i', '$1', $wkt);
        
        // Hapus koordinat Z dan M
        $wkt = preg_replace_callback('/(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)/', function($matches) {
            return $matches[1] . ' ' . $matches[2];
        }, $wkt);
        
        $wkt = preg_replace_callback('/(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)(?!\s+\-?\d)/', function($matches) {
            return $matches[1] . ' ' . $matches[2];
        }, $wkt);
        
        return $wkt;
    }

    // === DEBUG METHODS ===
    
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

    // === STATISTICS AND UTILITIES ===
    
    public function getStatistics(Request $request)
    {
        $dataType = $request->get('data_type');
        $subType = $request->get('sub_type');
        $year = $request->get('year');

        $query = DataSpatial::query();

        if ($dataType) {
            $query->where('data_type', $dataType);
        }

        if ($subType) {
            $query->where('sub_type', $subType);
        }

        if ($year) {
            $query->where('tahun', $year);
        }

        $stats = [
            'total_data' => $query->count(),
            'categories_count' => $query->distinct('kategori_id')->count(),
            'by_data_type' => DataSpatial::select('data_type', 'sub_type', DB::raw('count(*) as total'))
                                        ->when($dataType, fn($q) => $q->where('data_type', $dataType))
                                        ->when($subType, fn($q) => $q->where('sub_type', $subType))
                                        ->when($year, fn($q) => $q->where('tahun', $year))
                                        ->groupBy('data_type', 'sub_type')
                                        ->get(),
            'by_year' => DataSpatial::select('tahun', DB::raw('count(*) as total'))
                                   ->whereNotNull('tahun')
                                   ->when($dataType, fn($q) => $q->where('data_type', $dataType))
                                   ->when($subType, fn($q) => $q->where('sub_type', $subType))
                                   ->groupBy('tahun')
                                   ->orderBy('tahun', 'desc')
                                   ->get(),
            'geometry_stats' => [
                'with_geometry' => $query->whereNotNull('geom')->count(),
                'without_geometry' => $query->whereNull('geom')->count()
            ]
        ];

        // Bounds calculation
        $bounds = $query->whereNotNull('geom')
                       ->select(
                           DB::raw('ST_XMin(ST_Extent(geom)) as min_lng'),
                           DB::raw('ST_YMin(ST_Extent(geom)) as min_lat'),
                           DB::raw('ST_XMax(ST_Extent(geom)) as max_lng'),
                           DB::raw('ST_YMax(ST_Extent(geom)) as max_lat')
                       )
                       ->first();

        if ($bounds) {
            $stats['bounds'] = $bounds;
        }

        return response()->json([
            'success' => true,
            'statistics' => $stats,
            'filters' => [
                'data_type' => $dataType,
                'sub_type' => $subType,
                'year' => $year
            ]
        ]);
    }

    public function getDbfColumns(Request $request)
    {
        $dataType = $request->get('data_type');
        $subType = $request->get('sub_type');

        $query = "
            SELECT DISTINCT jsonb_object_keys(dbf_attributes) as column_name 
            FROM data_spatial 
            WHERE dbf_attributes IS NOT NULL
        ";

        $params = [];

        if ($dataType) {
            $query .= " AND data_type = ?";
            $params[] = $dataType;
        }

        if ($subType) {
            $query .= " AND sub_type = ?";
            $params[] = $subType;
        }

        $query .= " ORDER BY column_name";

        $columns = DB::select($query, $params);

        $columnNames = array_map(function($col) {
            return $col->column_name;
        }, $columns);

        return response()->json([
            'success' => true,
            'columns' => $columnNames,
            'filters' => [
                'data_type' => $dataType,
                'sub_type' => $subType
            ]
        ]);
    }

    public function getDbfColumnValues($column, Request $request)
    {
        $dataType = $request->get('data_type');
        $subType = $request->get('sub_type');

        $query = DataSpatial::whereNotNull('dbf_attributes')
                           ->whereRaw("dbf_attributes ? ?", [$column]);

        if ($dataType) {
            $query->where('data_type', $dataType);
        }

        if ($subType) {
            $query->where('sub_type', $subType);
        }

        $values = $query->pluck(DB::raw("DISTINCT dbf_attributes->>'{$column}'"))
                       ->filter(function($value) {
                           return !is_null($value) && $value !== '';
                       })
                       ->values();

        return response()->json([
            'success' => true,
            'column' => $column,
            'values' => $values,
            'filters' => [
                'data_type' => $dataType,
                'sub_type' => $subType
            ]
        ]);
    }

    public function getCategories(Request $request)
    {
        $dataType = $request->get('data_type');
        $subType = $request->get('sub_type');
        
        $categoryType = $this->getCategoryTypeByDataType($dataType, $subType);

        $categories = DB::table('data_spatial')
            ->join('categories', 'data_spatial.kategori_id', '=', 'categories.id')
            ->select('categories.id as kategori_id', 'categories.nama as kategori', DB::raw('COUNT(*) as count'))
            ->when($dataType, fn($q) => $q->where('data_spatial.data_type', $dataType))
            ->when($subType, fn($q) => $q->where('data_spatial.sub_type', $subType))
            ->when($categoryType, fn($q) => $q->where('categories.type', $categoryType))
            ->groupBy('categories.id', 'categories.nama')
            ->orderBy('categories.nama')
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $categories,
            'filters' => [
                'data_type' => $dataType,
                'sub_type' => $subType,
                'category_type' => $categoryType
            ]
        ]);
    }
}