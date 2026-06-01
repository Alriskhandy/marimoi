<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\FeatureImage;
use App\Models\Layer;
use App\Services\FeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Shapefile\ShapefileReader;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class FeatureWebController extends Controller
{
    protected FeatureService $featureService;

    public function __construct(FeatureService $featureService)
    {
        $this->featureService = $featureService;
    }

    public function index(Request $request, Layer $layer)
    {
        $filters = ['layer_id' => $layer->id];

        if ($request->get('year')) {
            $filters['year'] = $request->get('year');
        }

        $features = $this->featureService->getAllFeatures($filters, 20);
        $availableYears = Feature::where('layer_id', $layer->id)
            ->whereNotNull('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('backend.pages.layers.features.index', compact('layer', 'features', 'availableYears'));
    }

    public function create(Layer $layer)
    {
        return view('backend.pages.layers.features.create', compact('layer'));
    }

    public function mapData(Request $request, Layer $layer)
    {
        $query = DB::table('features as f')
            ->select(
                'f.id',
                'f.uuid',
                'f.properties',
                'f.year',
                DB::raw('ST_AsGeoJSON(f.geom) as geojson')
            )
            ->where('f.layer_id', $layer->id)
            ->whereNotNull('f.geom');

        if ($request->get('year')) {
            $query->where('f.year', $request->get('year'));
        }

        $rows = $query->limit(2000)->get();
        $features = $rows->map(function ($row) use ($layer) {
            $geometry   = json_decode($row->geojson);
            $properties = json_decode($row->properties, true) ?? [];

            return [
                'type'       => 'Feature',
                'geometry'   => $geometry,
                'properties' => array_merge($properties, [
                    '_id'         => $row->id,
                    '_uuid'       => $row->uuid,
                    '_year'       => $row->year,
                    '_detail_url' => route('layers.features.show', [$layer->id, $row->id]),
                ]),
            ];
        });

        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function store(Request $request, Layer $layer)
    {
        $request->validate([
            'year'       => 'nullable|integer|min:1900|max:' . (date('Y') + 5),
            'input_type' => 'required|in:geojson,shapefile,coordinates,kmz',
            'images.*'   => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        // Build extra properties from key-value pairs
        $extraProperties = [];
        $keys   = $request->input('prop_keys', []);
        $values = $request->input('prop_values', []);
        foreach ($keys as $i => $key) {
            if (!empty(trim($key))) {
                $extraProperties[trim($key)] = $values[$i] ?? '';
            }
        }

        try {
            DB::beginTransaction();

            $inputType = $request->input('input_type');
            [$count, $firstFeature] = match ($inputType) {
                'geojson'     => $this->processGeoJsonInput($request, $layer, $extraProperties),
                'shapefile'   => $this->processShapefileInput($request, $layer, $extraProperties),
                'coordinates' => $this->processCoordinatesInput($request, $layer, $extraProperties),
                'kmz'         => $this->processKmzInput($request, $layer, $extraProperties),
            };

            if ($firstFeature && $request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('feature-images', 'public');
                    FeatureImage::create([
                        'feature_id' => $firstFeature->id,
                        'file_path'  => $path,
                        'caption'    => '',
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('layers.features.index', $layer)
                ->with('success', "Berhasil menambahkan {$count} fitur.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing feature: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()])->withInput();
        }
    }

    private function saveFeatureRecord(Layer $layer, $year, array $properties, $geomRaw = null): Feature
    {
        $data = [
            'layer_id'   => $layer->id,
            'user_id'    => auth()->id(),
            'uuid'       => (string) Str::uuid(),
            'year'       => $year ?: null,
            'properties' => $properties,
        ];
        if ($geomRaw !== null) {
            $data['geom'] = $geomRaw;
        }
        return Feature::create($data);
    }

    private function processGeoJsonInput(Request $request, Layer $layer, array $extra): array
    {
        $request->validate(['geojson_file' => 'required|file']);

        $content = file_get_contents($request->file('geojson_file')->getRealPath());
        $geoJson = json_decode($content, true);

        if (!$geoJson || !isset($geoJson['features'])) {
            throw new \Exception('File GeoJSON tidak valid atau tidak berisi array features.');
        }

        $count        = 0;
        $firstFeature = null;
        $year         = $request->input('year');

        foreach ($geoJson['features'] as $f) {
            $geometry = $f['geometry'] ?? null;
            if (!$geometry) continue;

            $geoStr     = str_replace("'", "''", json_encode($geometry));
            $props      = array_merge((array) ($f['properties'] ?? []), $extra);
            $feature    = $this->saveFeatureRecord(
                $layer, $year, $props,
                DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('{$geoStr}'), 4326)")
            );
            $firstFeature = $firstFeature ?? $feature;
            $count++;
        }

        if ($count === 0) {
            throw new \Exception('File GeoJSON tidak berisi fitur geometri yang valid.');
        }

        return [$count, $firstFeature];
    }

    private function processShapefileInput(Request $request, Layer $layer, array $extra): array
    {
        $request->validate([
            'shp_file' => 'required|file',
            'shx_file' => 'required|file',
            'dbf_file' => 'required|file',
        ]);

        $folder = storage_path('app/shapefiles_feature');
        if (!file_exists($folder)) mkdir($folder, 0755, true);
        File::cleanDirectory($folder);

        $request->file('shp_file')->move($folder, 'data.shp');
        $request->file('shx_file')->move($folder, 'data.shx');
        $request->file('dbf_file')->move($folder, 'data.dbf');

        $shpPath = "$folder/data.shp";
        if (!file_exists($shpPath)) throw new \Exception('Gagal menyimpan file shapefile.');

        $reader       = new ShapefileReader($shpPath);
        $count        = 0;
        $firstFeature = null;
        $year         = $request->input('year');

        while ($geometry = $reader->fetchRecord()) {
            if ($geometry->isDeleted()) continue;

            $wkt        = $this->stripGeometryDimensions($geometry->getWKT());
            $dbfData    = $this->cleanDbfData($geometry->getDataArray());
            $props      = array_merge($dbfData, $extra);
            $safeWkt    = str_replace("'", "''", $wkt);
            $feature    = $this->saveFeatureRecord(
                $layer, $year, $props,
                DB::raw("ST_SetSRID(ST_GeomFromText('{$safeWkt}'), 4326)")
            );
            $firstFeature = $firstFeature ?? $feature;
            $count++;
        }

        if ($count === 0) throw new \Exception('Shapefile tidak berisi data geometrik yang valid.');

        return [$count, $firstFeature];
    }

    private function processCoordinatesInput(Request $request, Layer $layer, array $extra): array
    {
        $request->validate([
            'coordinates'             => 'required|array|min:1',
            'coordinates.*.latitude'  => 'required|numeric|between:-90,90',
            'coordinates.*.longitude' => 'required|numeric|between:-180,180',
        ]);

        $year         = $request->input('year');
        $count        = 0;
        $firstFeature = null;

        foreach ($request->input('coordinates') as $i => $coord) {
            if (empty($coord['latitude']) || empty($coord['longitude'])) continue;

            $lat  = (float) $coord['lat'] ?? (float) $coord['latitude'];
            $lng  = (float) $coord['lng'] ?? (float) $coord['longitude'];
            $name = $coord['name'] ?? "Fitur " . ($i + 1);
            $wkt  = "POINT({$lng} {$lat})";

            $props   = array_merge(['nama' => $name, 'latitude' => $lat, 'longitude' => $lng], $extra);
            $feature = $this->saveFeatureRecord(
                $layer, $year, $props,
                DB::raw("ST_SetSRID(ST_GeomFromText('{$wkt}'), 4326)")
            );
            $firstFeature = $firstFeature ?? $feature;
            $count++;
        }

        if ($count === 0) throw new \Exception('Tidak ada koordinat valid yang diberikan.');

        return [$count, $firstFeature];
    }

    private function processKmzInput(Request $request, Layer $layer, array $extra): array
    {
        $request->validate(['kmz_file' => 'required|file']);

        $file      = $request->file('kmz_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $tempDir   = storage_path('app/temp_kmz_feature');

        if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);
        File::cleanDirectory($tempDir);

        if ($extension === 'kmz') {
            $kmzPath = $tempDir . '/temp.kmz';
            $file->move($tempDir, 'temp.kmz');
            $zip = new ZipArchive();
            if ($zip->open($kmzPath) !== true) throw new \Exception('Gagal membuka file KMZ.');
            $kmlContent = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (pathinfo($zip->getNameIndex($i), PATHINFO_EXTENSION) === 'kml') {
                    $kmlContent = $zip->getFromIndex($i);
                    break;
                }
            }
            $zip->close();
            if (!$kmlContent) throw new \Exception('Tidak ada file KML di dalam arsip KMZ.');
        } else {
            $kmlContent = file_get_contents($file->getRealPath());
        }

        $dom = new DOMDocument();
        $dom->loadXML($kmlContent);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('kml', 'http://www.opengis.net/kml/2.2');

        $year         = $request->input('year');
        $count        = 0;
        $firstFeature = null;

        foreach ($xpath->query('//kml:Placemark') as $placemark) {
            $nameNode = $xpath->query('.//kml:name', $placemark)->item(0);
            $descNode = $xpath->query('.//kml:description', $placemark)->item(0);
            $nameText = $nameNode ? trim($nameNode->textContent) : 'Unnamed';
            $descText = $descNode ? trim($descNode->textContent) : '';

            foreach ($this->parseKmlGeometry($xpath, $placemark) as $wkt) {
                $props   = array_merge(['nama' => $nameText, 'deskripsi' => $descText], $extra);
                $safeWkt = str_replace("'", "''", $wkt);
                $feature = $this->saveFeatureRecord(
                    $layer, $year, $props,
                    DB::raw("ST_SetSRID(ST_GeomFromText('{$safeWkt}'), 4326)")
                );
                $firstFeature = $firstFeature ?? $feature;
                $count++;
            }
        }

        if ($count === 0) throw new \Exception('File KMZ/KML tidak berisi data geometrik yang valid.');

        return [$count, $firstFeature];
    }

    private function parseKmlGeometry(DOMXPath $xpath, $placemark): array
    {
        $geometries = [];

        foreach ($xpath->query('.//kml:Point/kml:coordinates', $placemark) as $point) {
            $parts = explode(',', trim($point->textContent));
            if (count($parts) >= 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                $geometries[] = "POINT(" . trim($parts[0]) . " " . trim($parts[1]) . ")";
            }
        }

        foreach ($xpath->query('.//kml:LineString/kml:coordinates', $placemark) as $ls) {
            $wkt = $this->kmlCoordsToLineStringWkt(trim($ls->textContent));
            if ($wkt) $geometries[] = $wkt;
        }

        foreach ($xpath->query('.//kml:Polygon', $placemark) as $poly) {
            $outer = $xpath->query('.//kml:outerBoundaryIs/kml:LinearRing/kml:coordinates', $poly)->item(0);
            if ($outer) {
                $wkt = $this->kmlCoordsToPolygonWkt(trim($outer->textContent));
                if ($wkt) $geometries[] = $wkt;
            }
        }

        return $geometries;
    }

    private function kmlCoordsToLineStringWkt(string $coordsText): ?string
    {
        $pts = [];
        foreach (preg_split('/\s+/', $coordsText) as $pt) {
            $c = explode(',', $pt);
            if (count($c) >= 2 && is_numeric($c[0]) && is_numeric($c[1])) {
                $pts[] = "{$c[0]} {$c[1]}";
            }
        }
        return count($pts) >= 2 ? "LINESTRING(" . implode(',', $pts) . ")" : null;
    }

    private function kmlCoordsToPolygonWkt(string $coordsText): ?string
    {
        $pts = [];
        foreach (preg_split('/\s+/', $coordsText) as $pt) {
            $c = explode(',', $pt);
            if (count($c) >= 2 && is_numeric($c[0]) && is_numeric($c[1])) {
                $pts[] = "{$c[0]} {$c[1]}";
            }
        }
        if (count($pts) < 4) return null;
        if ($pts[0] !== $pts[count($pts) - 1]) $pts[] = $pts[0];
        return "POLYGON((" . implode(',', $pts) . "))";
    }

    private function cleanDbfData(array $dbfData): array
    {
        $clean = [];
        foreach ($dbfData as $key => $value) {
            $k = trim($key);
            $v = is_string($value) ? trim($value) : $value;
            if (is_string($v) && !mb_check_encoding($v, 'UTF-8')) {
                $v = mb_convert_encoding($v, 'UTF-8', 'auto');
            }
            $clean[$k] = $v;
        }
        return $clean;
    }

    private function stripGeometryDimensions(string $wkt): string
    {
        $wkt = preg_replace(
            '/\b(MULTIPOLYGON|POLYGON|MULTIPOINT|POINT|MULTILINESTRING|LINESTRING|GEOMETRYCOLLECTION)(ZM|Z|M)\b/i',
            '$1',
            $wkt
        );
        $wkt = preg_replace_callback(
            '/(-?\d+\.?\d*)\s+(-?\d+\.?\d*)\s+(-?\d+\.?\d*)\s+(-?\d+\.?\d*)/',
            fn($m) => "{$m[1]} {$m[2]}",
            $wkt
        );
        return $wkt;
    }

    public function show(Layer $layer, Feature $feature)
    {
        $feature->load('images');
        return view('backend.pages.layers.features.show', compact('layer', 'feature'));
    }

    public function edit(Layer $layer, Feature $feature)
    {
        $feature->load('images');
        return view('backend.pages.layers.features.edit', compact('layer', 'feature'));
    }

    public function update(Request $request, Layer $layer, Feature $feature)
    {
        $request->validate([
            'year'       => 'nullable|integer|min:1900|max:' . (date('Y') + 5),
            'properties' => 'nullable|string',
        ]);

        $properties = [];
        if ($request->filled('properties')) {
            $decoded = json_decode($request->input('properties'), true);
            $properties = is_array($decoded) ? $decoded : [];
        }

        $this->featureService->updateFeature($feature->id, [
            'layer_id'   => $layer->id,
            'year'       => $request->input('year'),
            'properties' => $properties,
        ]);

        return redirect()
            ->route('layers.features.index', $layer)
            ->with('success', 'Fitur berhasil diperbarui.');
    }

    public function destroy(Layer $layer, Feature $feature)
    {
        $this->featureService->deleteFeatureWithImages($feature->id);
        return redirect()
            ->route('layers.features.index', $layer)
            ->with('success', 'Fitur berhasil dihapus.');
    }

    public function uploadImage(Request $request, Layer $layer, Feature $feature)
    {
        $request->validate([
            'image'   => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
            'caption' => 'nullable|string|max:255',
        ]);

        $path = $request->file('image')->store('feature-images', 'public');
        FeatureImage::create([
            'feature_id' => $feature->id,
            'file_path'  => $path,
            'caption'    => $request->input('caption', ''),
        ]);

        return back()->with('success', 'Gambar berhasil diupload.');
    }

    public function deleteImage(Layer $layer, Feature $feature, FeatureImage $image)
    {
        Storage::disk('public')->delete($image->file_path);
        $image->delete();
        return back()->with('success', 'Gambar berhasil dihapus.');
    }
}
