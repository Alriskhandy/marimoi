<?php

namespace Tests\Unit;

use App\Support\SpatialGeometryBatchImporter;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

/**
 * Regresi untuk SpatialGeometryBatchImporter — logika parsing geometri batch
 * (Shapefile/Koordinat manual/KMZ) yang dipakai SpatialLayerFeatureController::
 * store() untuk menyamakan "Tambah Data Spasial" di Layer baru dengan wizard
 * data-spatial/create lama. Parsing Shapefile (butuh berkas biner .shp/.shx/.dbf
 * asli) sengaja tidak diuji di sini — cukup berat untuk dibuat sebagai fixture
 * unit test; jalur itu divalidasi lewat smoke-test manual sebelum rilis.
 */
class SpatialGeometryBatchImporterTest extends TestCase
{
    private function importer(): SpatialGeometryBatchImporter
    {
        return new SpatialGeometryBatchImporter;
    }

    public function test_from_coordinates_builds_one_point_wkt_per_row(): void
    {
        $results = $this->importer()->fromCoordinates([
            ['name' => 'Titik A', 'latitude' => 0.8, 'longitude' => 127.5],
            ['name' => 'Titik B', 'latitude' => -1.2, 'longitude' => 128.1],
        ]);

        $this->assertCount(2, $results);
        $this->assertSame('POINT(127.5 0.8)', $results[0]['wkt']);
        $this->assertSame('Titik A', $results[0]['attributes']['NAMA']);
        $this->assertSame('POINT(128.1 -1.2)', $results[1]['wkt']);
    }

    public function test_from_coordinates_skips_rows_missing_lat_or_lng(): void
    {
        $results = $this->importer()->fromCoordinates([
            ['name' => 'Lengkap', 'latitude' => 0.8, 'longitude' => 127.5],
            ['name' => 'Tanpa longitude', 'latitude' => 0.9, 'longitude' => ''],
            ['name' => 'Kosong semua'],
        ]);

        $this->assertCount(1, $results);
        $this->assertSame('Lengkap', $results[0]['attributes']['NAMA']);
    }

    public function test_from_coordinates_throws_when_no_valid_row(): void
    {
        $this->expectException(\Exception::class);

        $this->importer()->fromCoordinates([
            ['name' => 'Kosong', 'latitude' => '', 'longitude' => ''],
        ]);
    }

    public function test_from_coordinates_uses_default_name_when_omitted(): void
    {
        $results = $this->importer()->fromCoordinates([
            ['latitude' => 0.8, 'longitude' => 127.5],
        ]);

        $this->assertSame('Titik 1', $results[0]['attributes']['NAMA']);
    }

    public function test_from_kmz_parses_point_placemark_from_kml_file(): void
    {
        $kml = <<<'KML'
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <Placemark>
      <name>Kantor Bappeda</name>
      <description>Lokasi uji</description>
      <Point><coordinates>127.5,0.8,0</coordinates></Point>
    </Placemark>
  </Document>
</kml>
KML;

        $path = tempnam(sys_get_temp_dir(), 'kml').'.kml';
        file_put_contents($path, $kml);
        $file = new UploadedFile($path, 'lokasi.kml', 'application/vnd.google-earth.kml+xml', null, true);

        $results = $this->importer()->fromKmz($file);

        $this->assertCount(1, $results);
        $this->assertSame('POINT(127.5 0.8)', $results[0]['wkt']);
        $this->assertSame('Kantor Bappeda', $results[0]['attributes']['NAMA']);
        $this->assertSame('kmz_import', $results[0]['attributes']['INPUT_TYPE']);

        unlink($path);
    }

    public function test_from_kmz_parses_polygon_placemark_and_closes_ring(): void
    {
        $kml = <<<'KML'
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <Placemark>
      <name>Area Uji</name>
      <Polygon>
        <outerBoundaryIs>
          <LinearRing>
            <coordinates>127.5,0.8,0 127.6,0.8,0 127.6,0.9,0 127.5,0.9,0</coordinates>
          </LinearRing>
        </outerBoundaryIs>
      </Polygon>
    </Placemark>
  </Document>
</kml>
KML;

        $path = tempnam(sys_get_temp_dir(), 'kml').'.kml';
        file_put_contents($path, $kml);
        $file = new UploadedFile($path, 'area.kml', 'application/vnd.google-earth.kml+xml', null, true);

        $results = $this->importer()->fromKmz($file);

        $this->assertCount(1, $results);
        $this->assertStringStartsWith('POLYGON((127.5 0.8,127.6 0.8,127.6 0.9,127.5 0.9', $results[0]['wkt']);
        // Ring harus tertutup — titik pertama diulang di akhir.
        $this->assertStringEndsWith('127.5 0.8))', $results[0]['wkt']);

        unlink($path);
    }

    public function test_from_kmz_throws_when_no_placemark_found(): void
    {
        $kml = <<<'KML'
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2"><Document></Document></kml>
KML;

        $path = tempnam(sys_get_temp_dir(), 'kml').'.kml';
        file_put_contents($path, $kml);
        $file = new UploadedFile($path, 'kosong.kml', 'application/vnd.google-earth.kml+xml', null, true);

        $this->expectException(\Exception::class);

        try {
            $this->importer()->fromKmz($file);
        } finally {
            unlink($path);
        }
    }
}
