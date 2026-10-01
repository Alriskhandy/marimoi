<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Shapefile\ShapefileReader;
use ZipArchive;

/**
 * Parsing geometri batch (Shapefile/Koordinat manual/KMZ-KML) jadi daftar
 * [wkt, attributes] generik — dipakai SpatialLayerFeatureController::store()
 * untuk menyamakan pengalaman "Tambah Data Spasial" di Layer baru dengan wizard
 * data-spatial/create lama (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md). Logika parsing diadaptasi dari
 * DataSpatialController, tapi sengaja TIDAK berbagi kode langsung (beda model
 * tujuan, beda cara simpan) — supaya perubahan di sini tidak berisiko merusak
 * alur data-spatial lama yang masih jadi halaman produksi utama.
 */
class SpatialGeometryBatchImporter
{
    /**
     * @return array<int, array{wkt: string, attributes: array<string, mixed>}>
     */
    public function fromShapefile(UploadedFile $shp, UploadedFile $shx, UploadedFile $dbf): array
    {
        $folder = storage_path('app/spatial-layer-shapefiles');
        if (! file_exists($folder)) {
            mkdir($folder, 0755, true);
        }
        File::cleanDirectory($folder);

        $shp->move($folder, 'data.shp');
        $shx->move($folder, 'data.shx');
        $dbf->move($folder, 'data.dbf');

        $shpPath = "$folder/data.shp";

        if (! file_exists($shpPath)) {
            throw new \Exception('Gagal menyimpan file shapefile.');
        }

        $reader = new ShapefileReader($shpPath);
        $results = [];

        while ($geometry = $reader->fetchRecord()) {
            if ($geometry->isDeleted()) {
                continue;
            }

            $wkt = $this->processGeometryDimensions($geometry->getWKT());
            $this->validateGeometryCoordinates($wkt);

            $results[] = [
                'wkt' => $wkt,
                'attributes' => $this->cleanDbfData($geometry->getDataArray()),
            ];
        }

        if (empty($results)) {
            throw new \Exception('Shapefile tidak berisi data geometrik yang valid.');
        }

        return $results;
    }

    /**
     * @param  array<int, array{name?: ?string, latitude?: mixed, longitude?: mixed}>  $rows
     * @return array<int, array{wkt: string, attributes: array<string, mixed>}>
     */
    public function fromCoordinates(array $rows): array
    {
        $results = [];

        foreach ($rows as $index => $row) {
            if (empty($row['latitude']) || empty($row['longitude'])) {
                continue;
            }

            $lat = (float) $row['latitude'];
            $lng = (float) $row['longitude'];
            $name = $row['name'] ?? ('Titik '.($index + 1));

            $results[] = [
                'wkt' => "POINT({$lng} {$lat})",
                'attributes' => [
                    'NAMA' => $name,
                    'LATITUDE' => $lat,
                    'LONGITUDE' => $lng,
                    'INPUT_TYPE' => 'manual_coordinates',
                ],
            ];
        }

        if (empty($results)) {
            throw new \Exception('Tidak ada koordinat valid yang dapat disimpan.');
        }

        return $results;
    }

    /**
     * @return array<int, array{wkt: string, attributes: array<string, mixed>}>
     */
    public function fromKmz(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $kmlContent = null;

        if ($extension === 'kmz') {
            $tempDir = storage_path('app/spatial-layer-temp-kmz');
            if (! file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            File::cleanDirectory($tempDir);

            $kmzPath = $tempDir.'/temp.kmz';
            $file->move($tempDir, 'temp.kmz');

            $zip = new ZipArchive;
            if ($zip->open($kmzPath) === true) {
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
            $kmlContent = file_get_contents($file->getRealPath());
        }

        if (! $kmlContent) {
            throw new \Exception('Tidak dapat menemukan file KML dalam arsip.');
        }

        return $this->parseKmlContent($kmlContent, $file->getClientOriginalName());
    }

    /**
     * @return array<int, array{wkt: string, attributes: array<string, mixed>}>
     */
    private function parseKmlContent(string $kmlContent, string $originalFileName): array
    {
        $dom = new DOMDocument;
        $dom->loadXML($kmlContent);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('kml', 'http://www.opengis.net/kml/2.2');

        $results = [];
        $placemarks = $xpath->query('//kml:Placemark');

        foreach ($placemarks as $placemark) {
            $name = $xpath->query('.//kml:name', $placemark)->item(0);
            $description = $xpath->query('.//kml:description', $placemark)->item(0);

            $nameText = $name ? trim($name->textContent) : 'Unnamed';
            $descText = $description ? trim($description->textContent) : '';

            foreach ($this->parseKmlGeometry($xpath, $placemark) as $wkt) {
                $results[] = [
                    'wkt' => $wkt,
                    'attributes' => [
                        'NAMA' => $nameText,
                        'DESCRIPTION' => $descText,
                        'INPUT_TYPE' => 'kmz_import',
                        'ORIGINAL_FILE' => $originalFileName,
                    ],
                ];
            }
        }

        if (empty($results)) {
            throw new \Exception('File KMZ/KML tidak berisi data geometrik yang valid.');
        }

        return $results;
    }

    /**
     * @return array<int, string>
     */
    private function parseKmlGeometry(DOMXPath $xpath, \DOMNode $placemark): array
    {
        $geometries = [];

        foreach ($xpath->query('.//kml:Point/kml:coordinates', $placemark) as $point) {
            $coords = explode(',', trim($point->textContent));
            if (count($coords) >= 2 && is_numeric(trim($coords[0])) && is_numeric(trim($coords[1]))) {
                $geometries[] = 'POINT('.trim($coords[0]).' '.trim($coords[1]).')';
            }
        }

        foreach ($xpath->query('.//kml:LineString/kml:coordinates', $placemark) as $lineString) {
            if ($wkt = $this->convertKmlCoordsToLineString(trim($lineString->textContent))) {
                $geometries[] = $wkt;
            }
        }

        foreach ($xpath->query('.//kml:Polygon', $placemark) as $polygon) {
            $outerBoundary = $xpath->query('.//kml:outerBoundaryIs/kml:LinearRing/kml:coordinates', $polygon)->item(0);
            if ($outerBoundary && $wkt = $this->convertKmlCoordsToPolygon(trim($outerBoundary->textContent))) {
                $geometries[] = $wkt;
            }
        }

        return $geometries;
    }

    private function convertKmlCoordsToLineString(string $coordsText): ?string
    {
        $wktPoints = $this->extractKmlPoints($coordsText);

        return count($wktPoints) >= 2 ? 'LINESTRING('.implode(',', $wktPoints).')' : null;
    }

    private function convertKmlCoordsToPolygon(string $coordsText): ?string
    {
        $wktPoints = $this->extractKmlPoints($coordsText);

        if (count($wktPoints) < 4) {
            return null;
        }

        if ($wktPoints[0] !== $wktPoints[count($wktPoints) - 1]) {
            $wktPoints[] = $wktPoints[0];
        }

        return 'POLYGON(('.implode(',', $wktPoints).'))';
    }

    /**
     * @return array<int, string>
     */
    private function extractKmlPoints(string $coordsText): array
    {
        $wktPoints = [];

        foreach (preg_split('/\s+/', trim($coordsText)) as $point) {
            $coords = explode(',', $point);
            if (count($coords) >= 2 && is_numeric(trim($coords[0])) && is_numeric(trim($coords[1]))) {
                $wktPoints[] = trim($coords[0]).' '.trim($coords[1]);
            }
        }

        return $wktPoints;
    }

    /**
     * @param  array<string, mixed>  $dbfData
     * @return array<string, mixed>
     */
    private function cleanDbfData(array $dbfData): array
    {
        $clean = [];
        foreach ($dbfData as $key => $value) {
            $cleanValue = is_string($value) ? trim($value) : $value;
            if (is_string($cleanValue) && ! mb_check_encoding($cleanValue, 'UTF-8')) {
                $cleanValue = mb_convert_encoding($cleanValue, 'UTF-8', 'auto');
            }
            $clean[trim($key)] = $cleanValue;
        }

        return $clean;
    }

    private function validateGeometryCoordinates(string $wkt): void
    {
        if (preg_match('/POINT\s*\(([\d\.\-]+)\s+([\d\.\-]+)\)/i', $wkt, $matches)) {
            $lng = (float) $matches[1];
            $lat = (float) $matches[2];

            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                throw new \Exception("Koordinat POINT berada di luar jangkauan WGS 84: ({$lng}, {$lat})");
            }
        }
    }

    private function processGeometryDimensions(string $wkt): string
    {
        if (str_contains($wkt, 'ZM') || str_contains($wkt, 'Z ') || str_contains($wkt, 'M ')) {
            return $wkt;
        }

        return $this->stripGeometryDimensions($wkt);
    }

    private function stripGeometryDimensions(string $wkt): string
    {
        $wkt = preg_replace('/\b(MULTIPOLYGON|POLYGON|MULTIPOINT|POINT|MULTILINESTRING|LINESTRING|GEOMETRYCOLLECTION)(ZM|Z|M)\b/i', '$1', $wkt);

        $wkt = preg_replace_callback('/(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)/', function ($matches) {
            return $matches[1].' '.$matches[2];
        }, $wkt);

        return preg_replace_callback('/(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)\s+(\-?\d+\.?\d*)(?!\s+\-?\d)/', function ($matches) {
            return $matches[1].' '.$matches[2];
        }, $wkt);
    }
}
