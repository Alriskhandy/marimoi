<?php

namespace App\Http\Controllers;

use App\Mail\AspirasiMail;
use App\Mail\TanggapanMail;
use App\Models\Aspirasi;
use App\Models\DataSpatial;
use App\Models\KategoriAspirasi;
use App\Models\LegacyCategory as Category;
use App\Models\ProjectFeedback;
use App\Models\Publication;
use App\Models\SharedMap;
use App\Models\User;
use App\Models\Visitor;
use App\Rules\ValidHCaptcha;
use App\Support\MapDataVersion;
use App\Support\PublicMapCatalog;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FrontendController extends Controller
{
    /**
     * Nama kategori aspirasi bawaan untuk jenis "kritik & saran" (bukan usulan pembangunan
     * dengan kategori/OPD spesifik). Dicari berdasarkan nama, bukan ID, karena ID baris ini
     * tidak dijamin selalu 1 (tergantung urutan seeding).
     */
    private const KATEGORI_KRITIK_SARAN = 'Kritik dan Saran';

    // public function index()
    // {
    //     // Ambil 6 data PSD secara random
    //     $psd = DataSpatial::where('data_type', 'proyek_strategis')
    //     ->where('sub_type', 'psd')
    //     ->orderBy('views', 'desc')->limit(6)->get();

    //     // Ambil 6 data PSN secara random
    //     $psn = DataSpatial::where('data_type', 'proyek_strategis')
    //     ->where('sub_type', 'psn')
    //     ->orderBy('views', 'desc')->limit(6)->get();

    //     // Ambil 6 data Pokir secara random
    //     $pokir = DataSpatial::where('data_type', 'pokir_dprd')
    //     ->orderBy('views', 'desc')->limit(6)->get();

    //     // Ambil 6 data Musrenbang secara random
    //     $musrenbang = DataSpatial::where('data_type', 'usulan_musrenbang')
    //     ->orderBy('views', 'desc')->limit(6)->get();

    //     // Menggabungkan semuanya dengan concat
    //     $dataPeta = collect()->concat($psd)->concat($psn)->concat($pokir)->concat($musrenbang);

    //     $links = [
    //         'psd' => 'proyek-strategis-daerah',
    //         'psn' => 'proyek-strategis-nasional',
    //         'pokir_dprd' => 'pokir-dprd',
    //         'usulan_musrenbang' => 'usulan-musrenbang',
    //     ];

    //     // Total masing-masing kategori
    //     $totalPsd = DataSpatial::where('data_type', 'proyek_strategis')->where('sub_type', 'psd')->count();
    //     $totalPsn = DataSpatial::where('data_type', 'proyek_strategis')->where('sub_type', 'psn')->count();
    //     $totalMusrenbang = DataSpatial::where('data_type', 'usulan_musrenbang')->count();
    //     $totalPokir = DataSpatial::where('data_type', 'pokir_dprd')->count();

    //     return view('frontend.pages.index-dark', compact(
    //         'dataPeta',
    //         'links',
    //         'totalPsd',
    //         'totalPsn',
    //         'totalMusrenbang',
    //         'totalPokir'
    //     ));
    // }

    public function indexDark()
    {
        $petaTematik = Category::where('type', 'tematik')
            ->where('is_active', true)
            ->where('gambar', '!=', null)
            ->get();

        $totalKritik = Aspirasi::where('jenis_aspirasi', 'kritik & saran')->count();
        $totalUsulan = Aspirasi::where('jenis_aspirasi', 'usulan')->count();

        // Real visitor counts using Visitor model scopes
        $visitorsToday = Visitor::today()->count();
        $visitorsWeek = Visitor::thisWeek()->count();
        $visitorsMonth = Visitor::thisMonth()->count();
        $visitorsTotal = Visitor::count();

        $spatial = $this->homeSpatialSummary();
        $totalPublikasi = Publication::count();

        return view('frontend.pages.home', compact(
            'spatial',
            'totalPublikasi',
            'petaTematik',
            'totalKritik',
            'totalUsulan',
            'visitorsToday',
            'visitorsWeek',
            'visitorsMonth',
            'visitorsTotal'
        ));
    }

    /**
     * Ringkasan data spasial untuk beranda: total objek, kategori teratas, dan
     * seluruh titik lokasi (geometri POINT) agar bisa dipetakan di peta interaktif.
     *
     * @return array{total: int, categories: int, top: array<int, object>, layers: array<int, object>, points: array<int, array<string, mixed>>}
     */
    /**
     * Bentuk wilayah Maluku Utara untuk latar visual beranda, diambil dari data nyata "Batas
     * Administrasi" (seluruh turunannya), disederhanakan agar ringan (sekitar 12 KB).
     * Poligon berkoordinat di luar selubung dibuang (data lama yang tersimpan dalam meter).
     *
     * @return array<int, mixed> daftar poligon; tiap poligon = daftar ring [lon, lat]
     */
    private function homeRegionShapes(): array
    {
        $category = Category::where('type', 'tematik')->where('nama', 'Batas Administrasi')->first();

        if (! $category) {
            return [];
        }

        $ids = implode(',', array_map('intval', Category::selfAndDescendantIds($category->id)));

        $rows = DB::select(
            "select ST_AsGeoJSON(g, 3) as geojson
             from (
                 select ST_SimplifyPreserveTopology(p.geom, 0.02) as g
                 from (
                     select (ST_Dump(ST_Force2D(geom))).geom as geom
                     from data_spatial_legacy_v1
                     where kategori_id in ({$ids})
                       and GeometryType(geom) in ('POLYGON', 'MULTIPOLYGON')
                       and ST_XMin(geom) > ? and ST_XMax(geom) < ? and ST_YMin(geom) > ? and ST_YMax(geom) < ?
                 ) p
                 where ST_Area(p.geom) > 0.0002
             ) t
             where g is not null and not ST_IsEmpty(g)",
            [self::HOME_LON_MIN, self::HOME_LON_MAX, self::HOME_LAT_MIN, self::HOME_LAT_MAX]
        );

        return collect($rows)
            ->map(fn ($row) => json_decode($row->geojson, true)['coordinates'] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    /** Selubung koordinat (derajat) wilayah Maluku Utara beserta margin. */
    private const HOME_LON_MIN = 123.0;

    private const HOME_LON_MAX = 131.5;

    private const HOME_LAT_MIN = -4.5;

    private const HOME_LAT_MAX = 5.5;

    private function homeSpatialSummary(): array
    {
        return Cache::remember('home.spatial-summary', 3600, function () {
            $top = DB::select(
                'select c.nama, c.warna, count(d.id) as total
                 from data_spatial_legacy_v1 d join categories_legacy_v1 c on c.id = d.kategori_id
                 group by c.id, c.nama, c.warna order by total desc limit 7'
            );

            // Hanya titik dengan koordinat derajat yang masuk akal untuk wilayah Maluku Utara. Data
            // yang tersimpan dalam satuan lain (mis. meter/Mercator) atau tanpa koordinat valid
            // dibuang agar tidak tergambar di luar kanvas atau menambah hitungan yang menyesatkan.
            // ST_X/ST_Y hanya boleh dipanggil pada POINT, jadi dibungkus CASE di subquery
            // (PostgreSQL tidak menjamin urutan evaluasi kondisi AND).
            $pointSource = "select d.id, d.kategori_id, d.deskripsi, d.tahun,
                        d.sumber_data, o.name as opd_pengelola, d.tanggal_data,
                        case when GeometryType(d.geom) = 'POINT' then ST_X(d.geom) end as px,
                        case when GeometryType(d.geom) = 'POINT' then ST_Y(d.geom) end as py
                 from data_spatial_legacy_v1 d
                 left join opd o on o.id = d.opd_pengelola_id";
            $pointBindings = [self::HOME_LON_MIN, self::HOME_LON_MAX, self::HOME_LAT_MIN, self::HOME_LAT_MAX];
            $inRange = 'p.px between ? and ? and p.py between ? and ?';

            $layers = DB::select(
                'select c.id, c.nama, c.warna, count(p.id) as total
                 from ('.$pointSource.') p join categories_legacy_v1 c on c.id = p.kategori_id
                 where '.$inRange.' group by c.id, c.nama, c.warna order by total desc',
                $pointBindings
            );

            $points = collect(DB::select(
                'select p.id, p.kategori_id as k, p.deskripsi as d, p.tahun as t,
                        p.sumber_data as sd, p.opd_pengelola as op, p.tanggal_data as td,
                        round(p.px::numeric, 5) as x, round(p.py::numeric, 5) as y
                 from ('.$pointSource.') p where '.$inRange,
                $pointBindings
            ))->map(fn ($row) => [
                'id' => $row->id,
                'k' => $row->k,
                'n' => $row->d ?: null,
                't' => $row->t,
                'sd' => $row->sd,
                'op' => $row->op,
                'td' => $row->td,
                'x' => (float) $row->x,
                'y' => (float) $row->y,
            ])->all();

            return [
                'total' => DB::table('data_spatial_legacy_v1')->count(),
                'categories' => Category::where('is_active', true)->count(),
                'top' => $top,
                'layers' => $layers,
                'points' => $points,
                'shapes' => $this->homeRegionShapes(),
            ];
        });
    }

    public function tentang()
    {
        return view('frontend.pages.tentang', [
            'spatial' => $this->homeSpatialSummary(),
            'totalPublikasi' => Publication::count(),
            'dukungan' => $this->dukunganVideos(),
        ]);
    }

    /**
     * Video testimoni "Dukungan Terhadap MARIMOI" (id video YouTube dan pemberi dukungan).
     *
     * @return array<int, array{id: string, title: string}>
     */
    private function dukunganVideos(): array
    {
        return array_map(fn (array $row) => ['id' => $row[0], 'title' => $row[1]], [
            ['cWA8hBj4PcE', 'Gubernur Provinsi Maluku Utara'],
            ['fcbyr-_O8VM', 'Wakil Gubernur Provinsi Maluku Utara'],
            ['SlncXrLMJrM', 'Sekretaris Daerah Provinsi Maluku Utara'],
            ['uJyzLgpJa8U', 'Direktur Pembangunan Indonesia Timur Kementrian PPN/BAPPENAS'],
            ['2wWShCIhAzs', 'Kepala DISKOMINFO dan Persandian Prov MALUT'],
            ['6fkOgICo_Xs', 'PLT. KADIKBUD Provinsi Maluku Utara'],
            ['tvWS8IOxy0w', 'Kepala DISPERKIM Provinsi Maluku Utara'],
            ['CDelrE8NNwc', 'Kepala Dinas PANGAN Provinsi Maluku Utara'],
            ['qKU3BAL2CBA', 'BAPPELITBANGDA Kota Ternate'],
            ['wJAVmcA_CDc', 'BAPPERIDA Kota Tidore Kepulauan'],
            ['oaV902ATMn8', 'BP3D Kabupaten Halmahera Barat'],
            ['Fxv5cDKptIQ', 'BAPPELITBANGDA Kabupaten Halmahera Selatan'],
            ['l7w_Y1WGWUY', 'BP4D Kabupaten Halmahera Timur'],
            ['-kda5JnpjLg', 'Sekretaris BAPPEDA Prov Maluku Utara'],
            ['COkXbg26hIw', 'Kabid PERAN BAPPEDA Prov Maluku Utara'],
            ['uVCVPWt6Pfk', 'Kabid SOSBUD BAPPEDA Prov Maluku Utara'],
            ['-oaJ37KpdSE', 'DUKUNGAN TERHADAP SISTEM MARIMOI'],
        ]);
    }

    public function faq()
    {
        return view('frontend.pages.faq');
    }

    public function reformer()
    {
        return view('frontend.pages.reformer');
    }

    public function publikasi()
    {
        return view('frontend.pages.publikasi');
    }

    public function aspirasi()
    {
        $aspirasi = KategoriAspirasi::where('nama_kategori', '!=', self::KATEGORI_KRITIK_SARAN)->get();

        return view('frontend.pages.aspirasi', compact('aspirasi'));
    }

    // TAMPILAN PETA //
    public function tematik()
    {
        // Mapset pilihan dari tautan "Lihat peta" di beranda (lihat lihatTematik()).
        $selectedCategory = session('selectedCategory');

        return view('frontend.pages.peta', compact('selectedCategory'));
    }

    public function lihatTematik($id)
    {
        try {
            $category = Category::findOrFail($id);

            // Tautan beranda masih memakai ID kategori lama; peta publik memakai nama V3.
            $mapsetName = PublicMapCatalog::nameForLegacyCategory((int) $category->id) ?? $category->nama;

            session(['selectedCategory' => $mapsetName]);
            session()->flash('info', "Memuat peta {$mapsetName}...");

            return redirect()->route('tampil.interaktif');
        } catch (\Exception $e) {
            Log::error('Error in lihatTematik: '.$e->getMessage());

            return redirect()->route('tampil.interaktif')
                ->with('error', 'Kategori peta tidak ditemukan.');
        }
    }

    /**
     * Generate slug unik untuk membagikan kombinasi layer + viewport Peta Interaktif.
     */
    public function createSharedMap(Request $request)
    {
        $validated = $request->validate([
            'layers' => 'required|array|min:1',
            'layers.*' => 'string|max:255',
            'viewport' => 'nullable|array',
            'viewport.lat' => 'nullable|numeric',
            'viewport.lng' => 'nullable|numeric',
            'viewport.zoom' => 'nullable|numeric',
            'data_type' => 'nullable|string|max:50',
            'sub_type' => 'nullable|string|max:50',
            'year' => 'nullable|integer',
            'filters' => 'nullable|array',
            'filters.kabupaten' => 'nullable|string|max:255',
            'filters.tahun' => 'nullable|integer',
            'filters.opd_pengelola' => 'nullable|string|max:255',
        ]);

        do {
            $slug = Str::random(8);
        } while (SharedMap::where('slug', $slug)->exists());

        $sharedMap = SharedMap::create([
            'slug' => $slug,
            'layers' => $validated['layers'],
            'viewport' => $validated['viewport'] ?? null,
            'data_type' => $validated['data_type'] ?? 'tematik',
            'sub_type' => $validated['sub_type'] ?? null,
            'year' => $validated['year'] ?? null,
            'filters' => $validated['filters'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'slug' => $sharedMap->slug,
            'url' => route('interaktif.share.show', $sharedMap->slug),
        ]);
    }

    /**
     * Muat halaman Peta Interaktif dengan state layer + viewport dari link share.
     */
    public function showSharedMap(string $slug)
    {
        $sharedMap = SharedMap::where('slug', $slug)->first();

        if (! $sharedMap || $sharedMap->isExpired()) {
            return view('frontend.pages.peta')
                ->with('sharedMapError', 'Link share tidak valid atau sudah kedaluwarsa.');
        }

        return view('frontend.pages.peta')
            ->with('sharedMapState', [
                'layers' => $sharedMap->layers,
                'viewport' => $sharedMap->viewport,
                'filters' => $sharedMap->filters,
            ]);
    }

    // NANTINYA DIISI PETA RPJMD //
    public function prioritas()
    {
        return view('frontend.pages.prioritas');
    }

    // ===== API PETA INTERAKTIF PUBLIK (dibaca public/frontend/js/map*.js) =====

    /**
     * Versi data Peta Interaktif. Browser membandingkannya dengan versi yang tersimpan bersama
     * cache peta; bila berbeda, cache dibuang (selain kedaluwarsa 24 jam).
     */
    public function tematikVersion()
    {
        return response()
            ->json(['version' => MapDataVersion::current()])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Pasangan data_type/sub_type untuk properti feature saat request tidak menyertakan
     * `type` (feature V3 tidak menyimpan jenis data per baris).
     *
     * @return array{0: ?string, 1: ?string} [data_type, sub_type]
     */
    private function dataTypeFromCategoryType(?string $categoryType): array
    {
        return match ($categoryType) {
            'tematik' => ['tematik', null],
            'usulan_musrenbang' => ['usulan_musrenbang', null],
            'pokir_dprd' => ['pokir_dprd', null],
            'psd' => ['proyek_strategis', 'psd'],
            'psn' => ['proyek_strategis', 'psn'],
            default => [$categoryType, null],
        };
    }

    /**
     * GET /geojson — data peta publik dari skema V3 (spatial_features, layers, layer_styles).
     *
     * - `?metadata_only=true`: daftar mapset untuk Katalog Peta (lihat PublicMapCatalog).
     * - Tanpa itu: feature GeoJSON per potongan (`limit` maks. 3000, `offset`), bisa disaring
     *   `kategori[]` (nama mapset), `year`, `bbox`, `search`, dan `dbf_filter`.
     *
     * Hanya Layer berstatus published yang dikirim. Deskripsi, sumber data, tanggal, tahun,
     * OPD, dan gambar dari data lama dibaca lewat LEFT JOIN ke data_spatial_legacy_v1
     * (`legacy_data_spatial_id`), karena belum semuanya ada di `properties` V3.
     */
    public function getGeojsonByDataType(Request $request)
    {
        try {
            $dataType = $request->get('type');
            $subType = $request->get('sub_type');
            $year = $request->get('year');
            $metadataOnly = $request->boolean('metadata_only');

            if ($metadataOnly) {
                return $this->getCategoriesMetadata($dataType, $subType);
            }

            $query = DB::table('spatial_features as sf')
                ->join('layers as l', 'l.id', '=', 'sf.layer_id')
                ->join('categories_v3 as cat_root', 'cat_root.id', '=', 'l.category_id')
                ->leftJoin('layer_styles as ls', 'ls.id', '=', 'l.default_style_id')
                ->leftJoin('data_spatial_legacy_v1 as ds', 'ds.id', '=', 'sf.legacy_data_spatial_id')
                ->leftJoin('opd', 'opd.id', '=', 'ds.opd_pengelola_id')
                ->select(
                    'sf.id',
                    'sf.layer_id',
                    'ds.uuid',
                    'sf.gambar',
                    'ds.gambar as legacy_gambar',
                    'l.legacy_category_id as kategori_id',
                    'sf.properties',
                    'sf.style_override',
                    DB::raw('ds.deskripsi as deskripsi'),
                    DB::raw("COALESCE(ds.sumber_data, sf.properties->>'sumber_data') as sumber_data"),
                    DB::raw("COALESCE(opd.name, sf.properties->>'opd_penanggung_jawab') as opd_pengelola"),
                    DB::raw("COALESCE(ds.tanggal_data, NULLIF(sf.properties->>'tanggal_data', '')::date) as tanggal_data"),
                    DB::raw("COALESCE(ds.tahun, NULLIF(sf.properties->>'tahun', '')::int) as tahun"),
                    DB::raw("ls.definition->>'icon' as icon"),
                    DB::raw("ls.definition->>'color' as warna"),
                    DB::raw("COALESCE((ls.definition->>'is_marker')::boolean, false) as is_marker"),
                    DB::raw('ST_AsGeoJSON(sf.geom) as geojson')
                );

            // Peta publik hanya menampilkan Layer berstatus published (draft tidak bocor).
            $query->where('l.status', 'published')
                ->whereNull('l.deleted_at')
                ->whereNull('cat_root.deleted_at');

            // Parameter `type`/`sub_type` sengaja tidak menyaring apa pun: skema V3 tidak lagi
            // menyimpan jenis data (tematik/PSD/dll.) pada kategori maupun layer.

            if ($year && is_numeric($year)) {
                $query->whereRaw("COALESCE(ds.tahun, NULLIF(sf.properties->>'tahun', '')::int) = ?", [intval($year)]);
            }

            // Pemuatan per mapset: frontend mengirim nama mapset di `kategori[]`.
            if ($request->has('kategori') && ! empty($request->kategori)) {
                $categories = is_array($request->kategori) ? $request->kategori : [$request->kategori];
                $categories = array_filter(array_map('trim', $categories));
                if (! empty($categories)) {
                    // `kategori[]` = nama mapset dari PublicMapCatalog (nama tampilan unik per Layer).
                    $query->whereIn('sf.layer_id', PublicMapCatalog::layerIdsForNames(array_values($categories)));
                }
            }

            // `bbox` = minLng,minLat,maxLng,maxLat; diabaikan bila koordinat di luar rentang derajat.
            if ($request->has('bbox') && ! empty($request->bbox)) {
                $bbox = explode(',', $request->bbox);
                if (count($bbox) === 4) {
                    $bbox = array_map('floatval', $bbox);
                    if (
                        $bbox[0] >= -180 && $bbox[0] <= 180 &&
                        $bbox[1] >= -90 && $bbox[1] <= 90 &&
                        $bbox[2] >= -180 && $bbox[2] <= 180 &&
                        $bbox[3] >= -90 && $bbox[3] <= 90
                    ) {
                        try {
                            $query->whereRaw('ST_Intersects(sf.geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))', $bbox);
                        } catch (\Exception $e) {
                            Log::warning('Bounding box filter failed: '.$e->getMessage());
                        }
                    }
                }
            }

            // Pencarian teks pada nama layer, deskripsi, dan seluruh atribut feature.
            if ($request->has('search') && ! empty($request->search)) {
                $search = trim($request->search);
                if (strlen($search) > 0) {
                    $query->where(function ($q) use ($search) {
                        $q->where('l.name', 'ILIKE', "%{$search}%")
                            ->orWhere('ds.deskripsi', 'ILIKE', "%{$search}%")
                            ->orWhereRaw('sf.properties::text ILIKE ?', ["%{$search}%"]);
                    });
                }
            }

            // `dbf_filter[ATRIBUT]=nilai`: atribut feature harus sama persis dengan nilai.
            if ($request->has('dbf_filter') && ! empty($request->dbf_filter) && is_array($request->dbf_filter)) {
                foreach ($request->dbf_filter as $attribute => $value) {
                    if (is_string($attribute) && ! empty($attribute)) {
                        try {
                            $query->whereRaw('sf.properties->? = ?::jsonb', [$attribute, json_encode($value)]);
                        } catch (\Exception $e) {
                            Log::warning("DBF filter failed for {$attribute}: ".$e->getMessage());
                        }
                    }
                }
            }

            $limit = min(intval($request->get('limit', 500)), 3000);
            $offset = max(0, intval($request->get('offset', 0)));

            // Urut id supaya potongan limit/offset berikutnya tidak tumpang tindih.
            $query->limit($limit)->offset($offset)->orderBy('sf.id');

            $startTime = microtime(true);
            $lokasis = $query->get();
            $queryTime = microtime(true) - $startTime;

            Log::info("Query executed in {$queryTime} seconds, returned ".$lokasis->count().' records');

            if ($queryTime > 30) {
                Log::warning("Slow query detected: {$queryTime} seconds");
            }

            // Kunci ini sudah dikirim sebagai field tersendiri (sudah diformat, mis. tanggal_data
            // d-m-Y), jadi tidak ikut disalin lagi dari properties mentah agar tidak tertimpa.
            $structuralKeys = ['sumber_data', 'tanggal_data', 'tahun', 'opd_penanggung_jawab'];
            $mapsetNames = PublicMapCatalog::namesById();

            $features = [];
            $processedCount = 0;

            foreach ($lokasis as $lokasi) {
                try {
                    $dbfAttributes = [];
                    if (! empty($lokasi->properties)) {
                        $decoded = is_string($lokasi->properties) ? json_decode($lokasi->properties, true) : $lokasi->properties;
                        if (is_array($decoded)) {
                            $dbfAttributes = array_diff_key($decoded, array_flip($structuralKeys));
                        }
                    }

                    $geometry = null;
                    if (! empty($lokasi->geojson)) {
                        if (is_string($lokasi->geojson)) {
                            $geometry = json_decode($lokasi->geojson);
                        } else {
                            $geometry = $lokasi->geojson;
                        }
                    }

                    [$featureDataType, $featureSubType] = $dataType
                        ? [$dataType, $subType]
                        : $this->dataTypeFromCategoryType(null);

                    // Foto dokumentasi: gambar fitur V3 lalu gambar data lama (tanpa duplikat).
                    $gambarList = collect([$lokasi->gambar, $lokasi->legacy_gambar])
                        ->filter()
                        ->unique()
                        ->map(fn (string $path) => asset('storage/'.$path))
                        ->values()
                        ->all();

                    $feature = [
                        'type' => 'Feature',
                        'properties' => array_merge([
                            'id' => $lokasi->id,
                            'uuid' => $lokasi->uuid,
                            'data_type' => $featureDataType,
                            'sub_type' => $featureSubType,
                            'gambar' => $gambarList[0] ?? null,
                            'gambar_list' => $gambarList,
                            'kategori_id' => $lokasi->kategori_id,
                            'kategori' => $mapsetNames[$lokasi->layer_id] ?? null,
                            'tahun' => $lokasi->tahun,
                            'deskripsi' => $lokasi->deskripsi,
                            'sumber_data' => $lokasi->sumber_data,
                            'opd_pengelola' => $lokasi->opd_pengelola,
                            'tanggal_data' => $lokasi->tanggal_data
                                ? Carbon::parse($lokasi->tanggal_data)->format('d-m-Y')
                                : null,
                            'icon' => $lokasi->icon,
                            'warna' => $lokasi->warna,
                            'is_marker' => (bool) $lokasi->is_marker,
                            // Style khusus Data Spasial ini (diatur per fitur di dashboard); null = ikut style Layer.
                            'style_override' => $lokasi->style_override ? json_decode($lokasi->style_override, true) : null,
                        ], $dbfAttributes),
                        'geometry' => $geometry,
                    ];

                    $features[] = $feature;
                    $processedCount++;
                } catch (\Exception $featureError) {
                    Log::error("Error processing feature {$lokasi->id}: ".$featureError->getMessage());

                    // Satu feature rusak tidak boleh menggagalkan seluruh potongan.
                    continue;
                }
            }

            $response = [
                'type' => 'FeatureCollection',
                'features' => $features,
                'meta' => [
                    'data_type' => $dataType,
                    'sub_type' => $subType,
                    'year' => $year,
                    'total_features' => count($features),
                    'limit' => $limit,
                    'offset' => $offset,
                    // Potongan penuh = kemungkinan masih ada data di offset berikutnya.
                    'has_more' => count($features) == $limit,
                    'query_time' => round($queryTime, 3),
                    'processed_count' => $processedCount,
                    'max_limit' => 3000,
                    'generated_at' => now()->toISOString(),
                ],
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error in getGeojsonByDataType: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Terjadi kesalahan saat memuat data.',
                'details' => config('app.debug') ? $e->getMessage() : null,
                'meta' => [
                    'limit' => 3000,
                    'max_limit' => 3000,
                    'generated_at' => now()->toISOString(),
                ],
            ], 500);
        }
    }

    /**
     * Nilai distinct Kabupaten/Kota, Tahun, dan OPD Pengelola untuk mengisi dropdown
     * filter Peta Interaktif tanpa harus menunggu layer tertentu dimuat/dicentang dulu
     * di browser (beda dari opsi yang digali progresif dari feature yang sudah
     * dirender di refreshFilterPanel() pada map.js).
     */
    public function getFilterOptions(Request $request)
    {
        $base = $this->publishedFeatureFilterQuery($request);

        $kabupaten = (clone $base)
            ->select(DB::raw("sf.properties->>'KABUPATEN' as value"))
            ->whereRaw("NULLIF(sf.properties->>'KABUPATEN', '') IS NOT NULL")
            ->distinct()
            ->pluck('value')
            ->sort()
            ->values();

        $tahun = (clone $base)
            ->select(DB::raw(self::FEATURE_TAHUN_SQL.' as value'))
            ->whereRaw(self::FEATURE_TAHUN_SQL.' IS NOT NULL')
            ->distinct()
            ->pluck('value')
            ->sortDesc()
            ->values();

        $opdPengelola = (clone $base)
            ->select(DB::raw(self::FEATURE_OPD_SQL.' as value'))
            ->whereRaw('NULLIF('.self::FEATURE_OPD_SQL.", '') IS NOT NULL")
            ->distinct()
            ->pluck('value')
            ->sort()
            ->values();

        return response()->json([
            'kabupaten' => $kabupaten,
            'tahun' => $tahun,
            'opd_pengelola' => $opdPengelola,
        ]);
    }

    /**
     * Mapset (nama tampilan Layer, sama dengan PublicMapCatalog) yang punya minimal satu
     * feature cocok dengan kombinasi filter, supaya katalog hanya menampilkan yang relevan.
     */
    public function getFilterCategories(Request $request)
    {
        $kabupaten = $request->get('kabupaten');
        $tahun = $request->get('tahun');
        $opdPengelola = $request->get('opd_pengelola');

        $query = $this->publishedFeatureFilterQuery($request);

        if ($kabupaten && is_string($kabupaten)) {
            $query->whereRaw("sf.properties->>'KABUPATEN' = ?", [$kabupaten]);
        }

        if ($tahun && is_numeric($tahun)) {
            $query->whereRaw(self::FEATURE_TAHUN_SQL.' = ?', [intval($tahun)]);
        }

        if ($opdPengelola && is_string($opdPengelola)) {
            $query->whereRaw(self::FEATURE_OPD_SQL.' = ?', [$opdPengelola]);
        }

        $names = PublicMapCatalog::namesById();
        $categories = $query->distinct()
            ->pluck('sf.layer_id')
            ->map(fn (string $layerId) => $names[$layerId] ?? null)
            ->filter()
            ->sort()
            ->values();

        return response()->json(['categories' => $categories]);
    }

    /**
     * Ekspresi tahun/OPD sama persis dengan yang diekspos `/geojson`, supaya filter di
     * server cocok dengan properti yang disaring refreshFilterPanel() di browser.
     */
    private const FEATURE_TAHUN_SQL = "COALESCE(ds.tahun, NULLIF(sf.properties->>'tahun', '')::int)";

    private const FEATURE_OPD_SQL = "COALESCE(opd.name, sf.properties->>'opd_penanggung_jawab')";

    /**
     * Feature milik Layer published (dengan join balik read-only ke data lama untuk tahun/OPD).
     */
    private function publishedFeatureFilterQuery(Request $request): Builder
    {
        $query = DB::table('spatial_features as sf')
            ->join('layers as l', 'l.id', '=', 'sf.layer_id')
            ->leftJoin('data_spatial_legacy_v1 as ds', 'ds.id', '=', 'sf.legacy_data_spatial_id')
            ->leftJoin('opd', 'opd.id', '=', 'ds.opd_pengelola_id')
            ->where('l.status', 'published')
            ->whereNull('l.deleted_at');

        $year = $request->get('year');
        if ($year && is_numeric($year)) {
            $query->whereRaw(self::FEATURE_TAHUN_SQL.' = ?', [intval($year)]);
        }

        return $query;
    }

    /**
     * Get only categories metadata without spatial data
     */
    private function getCategoriesMetadata($dataType, $subType)
    {
        try {
            $metadata = PublicMapCatalog::metadata();

            return response()->json(array_merge(['type' => 'MetadataCollection'], $metadata, [
                'meta' => [
                    'data_type' => $dataType,
                    'sub_type' => $subType,
                    'category_type' => $this->getCategoryTypeByDataType($dataType, $subType),
                    'total_root_categories' => count($metadata['root_categories']),
                    'total_categories' => count($metadata['all_categories']),
                    'generated_at' => now()->toISOString(),
                ],
            ]));
        } catch (\Exception $e) {
            Log::error('Error in getCategoriesMetadata: '.$e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Gagal memuat metadata kategori.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getCategoryTypeByDataType($dataType, $subType)
    {
        return match ($dataType) {
            'tematik' => 'tematik',
            'usulan_musrenbang' => 'usulan_musrenbang',
            'pokir_dprd' => 'pokir_dprd',
            'proyek_strategis' => in_array($subType, ['psn', 'psd']) ? $subType : 'psd',
            default => 'tematik',
        };
    }

    // DETAIL LOKASI //
    /**
     * Ekspresi SQL GeoJSON untuk halaman detail. Sebagian data lama tersimpan dalam meter
     * (Web Mercator) padahal berlabel SRID 4326; koordinat di luar rentang derajat
     * dikonversi kembali supaya peta detail mengarah ke lokasi yang benar.
     */
    private function geojsonSelectSql(): string
    {
        return 'ST_AsGeoJSON(CASE WHEN ST_XMin(geom) < -180 OR ST_XMax(geom) > 180 OR ST_YMin(geom) < -90 OR ST_YMax(geom) > 90
                THEN ST_Transform(ST_SetSRID(geom, 3857), 4326) ELSE geom END) as geojson';
    }

    public function detailPeta(Request $request, $uuid)
    {
        $project = DataSpatial::select('*', DB::raw($this->geojsonSelectSql()))
            ->with('opdPengelola')
            ->where('uuid', $uuid)
            ->firstOrFail();

        $project->increment('views');

        $project->geojson = json_decode($project->geojson);

        $projectType = $this->getProjectTypeFromRequest($request);

        return view('frontend.pages.detail', compact('project', 'projectType'));
    }

    public function detailPetaTematik(Request $request, $uuid)
    {
        $project = DataSpatial::select('*', DB::raw($this->geojsonSelectSql()))
            ->with('opdPengelola')
            ->where('uuid', $uuid)
            ->firstOrFail();

        $project->increment('views');

        $project->geojson = json_decode($project->geojson);

        $projectType = $this->getProjectTypeFromRequest($request);

        return view('frontend.pages.detailTematik', compact('project', 'projectType'));
    }

    /**
     * Store feedback for specific project type
     */
    public function store(Request $request)
    {
        // Rules untuk validasi inputan user
        $rules = [
            'data_spatial_id' => 'required',
            'nama_pemberi_aspirasi' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'nama_proyek' => 'required|string|max:255',
            'kabupaten_kota' => 'required|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'jenis_tanggapan' => 'required|in:keluhan,saran,apresiasi,pertanyaan',
            'tanggapan' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'h-captcha-response' => ['required', new ValidHCaptcha],
        ];

        // Cek Jenis Tanggapan, jika pengaduan maka wajib ada file
        if ($request->jenis_tanggapan === 'keluhan') {
            $rules['laporan_gambar'] = 'required|file|mimes:jpeg,png,jpg|max:5120';
        } else {
            $rules['laporan_gambar'] = 'nullable|file|mimes:jpeg,png,jpg|max:5120';
        }

        $messages = [
            'data_spatial_id.required' => 'Id Kegiatan tidak ada',
            'nama_pemberi_aspirasi.required' => 'Nama wajib diisi',
            'nama_pemberi_aspirasi.min' => 'Nama minimal 3 karakter',
            'nama_pemberi_aspirasi.max' => 'Nama maksimal 100 karakter',
            'jenis_tanggapan.required' => 'Jenis tanggapan wajib dipilih',
            'jenis_tanggapan.in' => 'Jenis tanggapan tidak valid',
            'tanggapan.required' => 'Tanggapan wajib diisi',
            'email.email' => 'Format email tidak valid',
            'phone.max' => 'Nomor telepon terlalu panjang',
            'laporan_gambar.required' => 'Lampiran gambar wajib untuk pengaduan',
            'laporan_gambar.file' => 'Lampiran harus berupa gambar dengan format yang benar',
            'laporan_gambar.mimes' => 'Format Lampiran harus jpeg, png, atau jpg',
            'laporan_gambar.max' => 'Ukuran file maksimal 5MB',
            'h-captcha-response.required' => 'Verifikasi CAPTCHA wajib diselesaikan',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Validate that the project exists using dynamic resolution
            $dataSpatialExists = DataSpatial::find($request->data_spatial_id);

            if (! $dataSpatialExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Proyek yang dipilih tidak ditemukan',
                ], 404);
            }

            $user = User::find($dataSpatialExists->user_id);

            // Data dari request user
            $data = $request->only([
                'data_spatial_id',
                'nama_pemberi_aspirasi',
                'nama_proyek',
                'kabupaten_kota',
                'kecamatan',
                'jenis_tanggapan',
                'tanggapan',
                'email',
                'phone',
                'latitude',
                'longitude',
            ]);

            // Tambahkan data status = pending (default);
            $data['status'] = 'pending';
            $data['opd_id'] = $user->opd_id;

            // Handle single file upload langsung
            if ($request->hasFile('laporan_gambar')) {
                $file = $request->file('laporan_gambar');

                if ($file->isValid()) {
                    // Generate unique filename
                    $timestamp = now()->timestamp;
                    $randomString = Str::random(13);
                    $extension = $file->getClientOriginalExtension();
                    $filename = $timestamp.'_'.$randomString.'.'.$extension;

                    // Store file
                    $path = $file->storeAs('aspirasi_lampiran', $filename, 'public');

                    if ($path) {
                        $data['laporan_gambar'] = $filename;

                        Log::info('File uploaded', [
                            'original' => $file->getClientOriginalName(),
                            'saved' => $filename,
                            'size' => $file->getSize(),
                        ]);
                    }
                }
            }

            ProjectFeedback::create($data);

            // Data untuk user
            $userData = [
                'nama' => $request->nama_pemberi_aspirasi,
                'email' => $request->email,
                'tanggapan' => $request->tanggapan,
                'tanggal' => now()->format('d-m-Y H:i'),
            ];

            // Data untuk admin
            $adminData = [
                'nama' => $request->nama_pemberi_aspirasi,
                'email' => $request->email,
                'tanggapan' => $request->tanggapan,
                'tanggal' => now()->format('d-m-Y H:i'),
                'nama_proyek' => $request->nama_proyek,
                'kabupaten_kota' => $request->kabupaten_kota,
                'kecamatan' => $request->kecamatan,
                'jenis_tanggapan' => $request->jenis_tanggapan,
            ];

            // Kirim email penerimaan ke pengguna jika ada email
            if ($request->filled('email')) {
                Mail::to($request->email)->queue(new TanggapanMail($userData, 'penerimaan'));
            }

            // Kirim notifikasi ke admin (gunakan email admin dari user/project terkait)
            $adminEmail = $user->email ?? config('mail.from.address');
            Mail::to($adminEmail)->queue(new TanggapanMail($adminData, 'admin'));

            return response()->json([
                'status' => 'success',
                'message' => 'Tanggapan berhasil ditambahkan',
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing feedback: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function aspirasiStore(Request $request)
    {
        // Base rules yang berlaku untuk semua jenis aspirasi
        $rules = [
            'nama_pengirim' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'alamat' => 'required|string|min:5|max:200',
            'jenis_aspirasi' => 'required|in:usulan,kritik & saran',
            'judul_aspirasi' => 'required|string|min:5|max:150',
            'isi_aspirasi' => 'required|string|min:10|max:1000',
            'agreement' => 'required|accepted',
            'h-captcha-response' => ['required', new ValidHCaptcha],
        ];

        // Validasi berdasarkan jenis aspirasi
        if ($request->jenis_aspirasi === 'usulan') {
            $rules['kategori_aspirasi_id'] = 'required|exists:kategori_aspirasi,id';
            $rules['latitude'] = 'required|numeric|between:-90,90';
            $rules['longitude'] = 'required|numeric|between:-180,180';
            $rules['lampiran'] = 'required|file|mimes:jpeg,png,jpg,doc,docx,pdf,|max:5120';
        } elseif ($request->jenis_aspirasi === 'kritik & saran') {
            // Untuk Kritik & Saran: semua field wajib diisi kecuali lampiran (tidak wajib)
            $rules['lampiran'] = 'nullable|file|mimes:jpeg,png,jpg,doc,docx,pdf,|max:5120';
            $rules['latitude'] = 'nullable|numeric|between:-90,90';
            $rules['longitude'] = 'nullable|numeric|between:-180,180';
        }

        // Custom messages untuk validasi
        $messages = [
            'nama_pengirim.required' => 'Nama lengkap wajib diisi',
            'nama_pengirim.min' => 'Nama lengkap minimal 3 karakter',
            'nama_pengirim.max' => 'Nama lengkap maksimal 100 karakter',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'phone.required' => 'Nomor WhatsApp wajib diisi',
            'phone.regex' => 'Format nomor WhatsApp tidak valid (contoh: 08xxxxxxxxxx)',
            'alamat.required' => 'Alamat wajib diisi',
            'alamat.min' => 'Alamat minimal 5 karakter',
            'alamat.max' => 'Alamat maksimal 200 karakter',
            'jenis_aspirasi.required' => 'Jenis aspirasi wajib dipilih',
            'jenis_aspirasi.in' => 'Jenis aspirasi tidak valid',
            'judul_aspirasi.required' => 'Judul aspirasi wajib diisi',
            'judul_aspirasi.min' => 'Judul aspirasi minimal 5 karakter',
            'judul_aspirasi.max' => 'Judul aspirasi maksimal 150 karakter',
            'isi_aspirasi.required' => 'Isi aspirasi wajib diisi',
            'isi_aspirasi.min' => 'Isi aspirasi minimal 10 karakter',
            'isi_aspirasi.max' => 'Isi aspirasi maksimal 1000 karakter',
            'kategori_aspirasi_id.required' => 'Kategori usulan wajib dipilih untuk usulan pembangunan',
            'kategori_aspirasi_id.exists' => 'Kategori usulan tidak valid',
            'latitude.required' => 'Lokasi wajib diisi untuk usulan pembangunan',
            'longitude.required' => 'Lokasi wajib diisi untuk usulan pembangunan',
            'latitude.numeric' => 'Koordinat latitude harus berupa angka',
            'latitude.between' => 'Koordinat latitude tidak valid',
            'longitude.numeric' => 'Koordinat longitude harus berupa angka',
            'longitude.between' => 'Koordinat longitude tidak valid',
            'lampiran.required' => 'Lampiran wajib disertakan untuk usulan pembangunan.',
            'lampiran.file' => 'Lampiran harus berupa file',
            'lampiran.mimes' => 'Format lampiran: JPG, PNG, JPEG, DOC, DOCX, atau PDF.',
            'lampiran.max' => 'Ukuran lampiran maksimal 5MB',
            'agreement.required' => 'Anda harus menyetujui syarat dan ketentuan',
            'agreement.accepted' => 'Anda harus menyetujui syarat dan ketentuan',
            'h-captcha-response.required' => 'Verifikasi CAPTCHA wajib diselesaikan',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        // Jika validasi gagal, kembalikan response dengan error
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal. Periksa kembali data Anda.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Data dari request user
            $data = $request->only([
                'nama_pengirim',
                'email',
                'phone',
                'alamat',
                'jenis_aspirasi',
                'judul_aspirasi',
                'isi_aspirasi',
            ]);

            // Set default status ke pending;
            $data['status'] = 'pending';

            // Ambil data admin dan OPD di awal untuk memastikan ketersediaan data
            $adminData = User::where('role_id', 1)->first();
            $opdData = null;

            if ($request->jenis_aspirasi === 'usulan' && $request->kategori_aspirasi_id) {
                $opdData = KategoriAspirasi::with('opd')->find($request->kategori_aspirasi_id);
            }

            // Handle data berdasarkan jenis aspirasi
            if ($request->jenis_aspirasi === 'usulan') {
                $data['admin_id'] = $opdData && $opdData->opd ? $opdData->opd->id : ($adminData ? $adminData->id : null);
                $data['kategori_aspirasi_id'] = $request->kategori_aspirasi_id;
                $data['latitude'] = $request->latitude;
                $data['longitude'] = $request->longitude;
            } else {
                $kategoriKritikSaran = KategoriAspirasi::where('nama_kategori', self::KATEGORI_KRITIK_SARAN)->first();

                if (! $kategoriKritikSaran) {
                    Log::error('Kategori aspirasi default "'.self::KATEGORI_KRITIK_SARAN.'" tidak ditemukan.');

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Sistem belum siap menerima kritik & saran. Silakan hubungi admin.',
                    ], 500);
                }

                $data['admin_id'] = $adminData ? $adminData->id : null;
                $data['kategori_aspirasi_id'] = $kategoriKritikSaran->id;
                $data['latitude'] = $request->latitude ?? null;
                $data['longitude'] = $request->longitude ?? null;
            }

            // Handle file(lampiran) upload
            if ($request->hasFile('lampiran')) {
                $file = $request->file('lampiran');

                if ($file->isValid()) {
                    try {
                        // Generate unique filename
                        $timestamp = now()->timestamp;
                        $randomString = Str::random(13);
                        $extension = $file->getClientOriginalExtension();
                        $filename = $timestamp.'_'.$randomString.'.'.$extension;

                        // Store file
                        $path = $file->storeAs('aspirasi_lampiran', $filename, 'public');

                        if ($path) {
                            $data['lampiran'] = $filename;
                            Log::info('Lampiran uploaded', [
                                'original' => $file->getClientOriginalName(),
                                'saved' => $filename,
                                'size' => $file->getSize(),
                            ]);
                        } else {
                            Log::error('Failed to store file');
                        }
                    } catch (\Exception $e) {
                        Log::error('File upload error: '.$e->getMessage());
                    }
                }
            }

            // Create aspirasi record
            $aspirasi = Aspirasi::create($data);

            // Prepare data for notifications
            $userData = [
                'id_aspirasi' => $aspirasi->id,
                'nomor_tiket' => $aspirasi->nomor_tiket,
                'nama_pengirim' => $data['nama_pengirim'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'alamat' => $data['alamat'],
                'jenis_aspirasi' => $data['jenis_aspirasi'],
                'judul_aspirasi' => $data['judul_aspirasi'],
                'isi_aspirasi' => $data['isi_aspirasi'],
                'tanggal' => $aspirasi->created_at->format('d-m-Y H:i:s'),
                'kategori_aspirasi' => $opdData ? $opdData->nama_kategori : 'N/A',
                'opd_terkait' => ($opdData && $opdData->opd) ? $opdData->opd->singkatan : 'N/A',
            ];

            // 1. Kirim email konfirmasi ke user (masyarakat)
            if ($request->filled('email')) {
                try {
                    Mail::to($request->email)->queue(new AspirasiMail($userData, 'penerimaan'));
                    Log::info('Confirmation email queued for user: '.$request->email);
                } catch (\Exception $e) {
                    Log::error('Failed to queue user email: '.$e->getMessage());
                }
            }

            // 2. Kirim email notifikasi ke admin sistem
            if ($adminData && ! empty($adminData->email)) {
                try {
                    Mail::to($adminData->email)->queue(new AspirasiMail($userData, 'admin'));
                    Log::info('Admin notification email queued for: '.$adminData->email);
                } catch (\Exception $e) {
                    Log::error('Failed to queue admin email: '.$e->getMessage());
                }
            }

            // 3. Kirim email notifikasi ke OPD terkait (hanya untuk usulan)
            if ($request->jenis_aspirasi === 'usulan' && $opdData && $opdData->opd && ! empty($opdData->opd->email)) {
                try {
                    Mail::to($opdData->opd->email)->queue(new AspirasiMail($userData, 'opd'));
                    Log::info('OPD notification email queued for: '.$opdData->opd->email);
                } catch (\Exception $e) {
                    Log::error('Failed to queue OPD email: '.$e->getMessage());
                }
            }

            // Log successful creation
            Log::info('Aspirasi created successfully', [
                'id' => $aspirasi->id,
                'jenis' => $data['jenis_aspirasi'],
                'pengirim' => $data['nama_pengirim'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Aspirasi Anda telah berhasil dikirim. Email konfirmasi telah dikirim ke alamat email Anda.',
                'data' => [
                    'id' => $aspirasi->id,
                    'nomor_tiket' => $aspirasi->nomor_tiket,
                    'tanggal' => $aspirasi->created_at->format('d-m-Y H:i:s'),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error storing aspirasi', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi admin jika masalah berlanjut.',
            ], 500);
        }
    }

    /**
     * Tampilkan form pelacakan status aspirasi publik.
     */
    public function aspirasiLacak()
    {
        return view('frontend.pages.aspirasi-lacak');
    }

    /**
     * Cari aspirasi berdasarkan nomor tiket + verifikasi kepemilikan (email/phone).
     * Pesan error untuk "tidak ditemukan" dan "kontak tidak cocok" sengaja sama
     * supaya endpoint ini tidak bisa dipakai untuk enumerasi nomor tiket valid.
     */
    public function aspirasiLacakCari(Request $request)
    {
        $validated = $request->validate([
            'nomor_tiket' => 'required|string|max:30',
            'kontak' => 'required|string|max:255',
        ], [
            'nomor_tiket.required' => 'Nomor tiket wajib diisi.',
            'kontak.required' => 'Email atau nomor WhatsApp wajib diisi.',
        ]);

        $aspirasi = Aspirasi::where('nomor_tiket', trim($validated['nomor_tiket']))->first();

        $cocok = $aspirasi && (
            ($aspirasi->email && Str::lower($aspirasi->email) === Str::lower(trim($validated['kontak'])))
            || ($aspirasi->phone && $this->normalizeTelepon($aspirasi->phone) === $this->normalizeTelepon($validated['kontak']))
        );

        if (! $cocok) {
            return view('frontend.pages.aspirasi-lacak', [
                'notFound' => true,
            ])->withInput($request->only('nomor_tiket'));
        }

        return view('frontend.pages.aspirasi-lacak', [
            'aspirasi' => $aspirasi,
        ]);
    }

    /**
     * Normalisasi nomor telepon untuk pencocokan: buang karakter non-digit,
     * lalu samakan prefix 0/62 supaya "0812...", "62812...", "+62812..." dianggap sama.
     */
    private function normalizeTelepon(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return preg_replace('/^(0|62)/', '', $digits) ?? $digits;
    }

    /**
     * Handle lampiran upload
     */
    private function handleLampiranUpload($file)
    {
        try {
            $fileName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

            // Ensure directory exists
            $uploadPath = storage_path('app/public/aspirasi_lampiran');
            if (! file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $file->storeAs('public/aspirasi_lampiran', $fileName);

            return $fileName;
        } catch (\Exception $e) {
            Log::error('Error uploading lampiran: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Handle image upload
     */
    private function handleImageUpload($image)
    {
        try {
            $imageName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();

            // Ensure directory exists
            $uploadPath = storage_path('app/public/feedback_images');
            if (! file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $image->storeAs('public/feedback_images', $imageName);

            return $imageName;
        } catch (\Exception $e) {
            Log::error('Error uploading image: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Check if project exists safely
     */
    private function checkProjectExists($projectId)
    {
        try {
            return DataSpatial::where('id', $projectId)->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Determine project type from request
     */
    private function getProjectTypeFromRequest(Request $request)
    {
        // Check if project_type is passed as parameter
        if ($request->has('project_type')) {
            return $request->get('project_type');
        }

        // Determine from URL path
        $path = $request->path();

        if (str_contains($path, 'pokir/')) {
            return 'pokir_dprd';
        } elseif (str_contains($path, 'usulan/')) {
            return 'usulan_musrenbang';
        } elseif (str_contains($path, 'nasional/')) {
            return 'proyek_strategis_nasional';
        } elseif (str_contains($path, 'daerah/')) {
            return 'proyek_strategis_daerah';
        } elseif (str_contains($path, 'lokasi/')) {
            return 'lokasi';
        }

        return 'all'; // Default to show all
    }
}
