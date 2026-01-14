<?php

namespace Database\Factories;

use App\Models\DataSpatial;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataSpatialFactory extends Factory
{
    protected $model = DataSpatial::class;

    public function definition(): array
    {
        // Hanya generate data type tematik
        $dataType = 'tematik';
        $subType = null;

        // Geometry type yang akan dibuat
        $geometryType = $this->faker->randomElement(['Point', 'LineString', 'Polygon']);

        // Generate geometry berdasarkan type
        $geometry = $this->generateGeometry($geometryType);

        // Generate realistic DBF attributes untuk tematik saja
        $dbfAttributes = $this->generateTematikDbfAttributes();

        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? 1,
            'uuid' => 'MARIMOI-' . $this->faker->unique()->randomNumber(8),
            'data_type' => $dataType,
            'sub_type' => $subType,
            'gambar' => null,
            'kategori_id' => Category::where('type', 'tematik')->inRandomOrder()->first()?->id ?? 2,
            'deskripsi' => $this->generateTematikDescription(),
            'dbf_attributes' => $dbfAttributes,
            'tahun' => null,
            'views' => 0,
            'geom' => DB::raw($geometry),
        ];
    }

    /**
     * Generate geometry based on type untuk wilayah Maluku Utara
     */
    private function generateGeometry(string $type): string
    {
        switch ($type) {
            case 'Point':
                return $this->generatePoint();
            case 'LineString':
                return $this->generateLineString();
            case 'Polygon':
                return $this->generatePolygon();
            default:
                return $this->generatePoint();
        }
    }

    /**
     * Generate Point geometry
     */
    private function generatePoint(): string
    {
        $lat = $this->faker->randomFloat(6, -2.0, 3.0);     // Maluku Utara latitude
        $lng = $this->faker->randomFloat(6, 126.0, 129.5);  // Maluku Utara longitude
        
        return "ST_SetSRID(ST_MakePoint($lng, $lat), 4326)";
    }

    /**
     * Generate LineString geometry (untuk jalan, sungai, dll)
     */
    private function generateLineString(): string
    {
        $startLat = $this->faker->randomFloat(6, -2.0, 3.0);
        $startLng = $this->faker->randomFloat(6, 126.0, 129.5);
        
        $points = [[$startLng, $startLat]];
        
        // Generate 3-8 points untuk LineString
        $numPoints = $this->faker->numberBetween(3, 8);
        $currentLat = $startLat;
        $currentLng = $startLng;
        
        for ($i = 1; $i < $numPoints; $i++) {
            // Small incremental changes untuk realistic path
            $currentLat += $this->faker->randomFloat(6, -0.01, 0.01);
            $currentLng += $this->faker->randomFloat(6, -0.01, 0.01);
            
            // Keep within Maluku Utara bounds
            $currentLat = max(-2.0, min(3.0, $currentLat));
            $currentLng = max(126.0, min(129.5, $currentLng));
            
            $points[] = [$currentLng, $currentLat];
        }
        
        $lineString = 'LINESTRING(' . implode(',', array_map(function($point) {
            return $point[0] . ' ' . $point[1];
        }, $points)) . ')';
        
        return "ST_SetSRID(ST_GeomFromText('$lineString'), 4326)";
    }

    /**
     * Generate Polygon geometry (untuk area, bangunan, dll)
     */
    private function generatePolygon(): string
    {
        $centerLat = $this->faker->randomFloat(6, -2.0, 3.0);
        $centerLng = $this->faker->randomFloat(6, 126.0, 129.5);
        
        // Generate rectangular polygon
        $size = $this->faker->randomFloat(6, 0.001, 0.01); // Small area
        
        $points = [
            [$centerLng - $size, $centerLat - $size],
            [$centerLng + $size, $centerLat - $size],
            [$centerLng + $size, $centerLat + $size],
            [$centerLng - $size, $centerLat + $size],
            [$centerLng - $size, $centerLat - $size], // Close polygon
        ];
        
        $polygon = 'POLYGON((' . implode(',', array_map(function($point) {
            return $point[0] . ' ' . $point[1];
        }, $points)) . '))';
        
        return "ST_SetSRID(ST_GeomFromText('$polygon'), 4326)";
    }

    /**
     * Generate DBF attributes specifically for tematik data
     */
    private function generateTematikDbfAttributes(): array
    {
        $namaObjek = $this->generateTematikName();
        
        return [
            'NAMA' => $namaObjek,
            'INPUT_TYPE' => 'factory_generated',
            'DESCRIPTION' => $this->generateTematikDescription(),
            'ORIGINAL_FILE' => 'factory_data.geojson',
            'KABUPATEN' => $this->faker->randomElement([
                'Kota Ternate', 'Kota Tidore Kepulauan', 'Halmahera Barat', 
                'Halmahera Tengah', 'Halmahera Timur', 'Halmahera Selatan',
                'Halmahera Utara', 'Kepulauan Sula', 'Pulau Morotai', 'Pulau Taliabu'
            ]),
            'PROVINSI' => 'Maluku Utara',
            'KECAMATAN' => $this->generateKecamatan(),
            'KELURAHAN' => $this->generateKelurahan(),
            'JENIS' => $this->faker->randomElement(['Fasilitas Umum', 'Infrastruktur', 'Lingkungan', 'Ekonomi']),
            'KONDISI' => $this->faker->randomElement(['Baik', 'Sedang', 'Rusak', 'Baru']),
        ];
    }

    /**
     * Generate description specifically for tematik data
     */
    private function generateTematikDescription(): string
    {
        $descriptions = [
            'Fasilitas umum yang melayani masyarakat Maluku Utara',
            'Infrastruktur penting untuk mendukung pembangunan daerah',
            'Objek tematik yang menjadi perhatian pemerintah daerah',
            'Fasilitas pendukung kehidupan masyarakat',
            'Infrastruktur strategis Maluku Utara',
            'Objek vital bagi masyarakat lokal',
            'Fasilitas yang membutuhkan pemeliharaan rutin',
            'Infrastruktur penunjang aktivitas masyarakat'
        ];

        return $this->faker->randomElement($descriptions);
    }

    /**
     * Generate tematik object names
     */
    private function generateTematikName(): string
    {
        $objek = [
            'Masjid Al-Hikmah', 'Gereja Santo Yosef', 'Pura Agung', 'Vihara Dharma',
            'SD Negeri 1', 'SMP Negeri 2', 'SMA Negeri 3', 'Universitas Maluku Utara',
            'Puskesmas Kota', 'RSUD Dr. H. Chasan Boesoirie', 'Poliklinik Umum',
            'Kantor Camat', 'Kantor Lurah', 'Balai Desa', 'Kantor Pos',
            'Bank BRI', 'Bank Mandiri', 'ATM BCA', 'Koperasi Maju Bersama',
            'Pasar Sentral', 'Toko Swalayan', 'Warung Makan Padang', 'Restoran Seafood',
            'Hotel Bintang Lima', 'Penginapan Melati', 'Homestay Bahari',
            'Terminal Bus', 'Stasiun Kereta Api', 'Pelabuhan Ferry', 'Bandara Sultan Babber',
            'Pos Polisi', 'Kantor Damkar', 'Koramil', 'Satpol PP',
            'Lapangan Sepak Bola', 'Gedung Olahraga', 'Kolam Renang', 'Taman Kota',
            'Museum Kedaton Sultan', 'Benteng Tolukko', 'Masjid Sultan',
            'Pantai Sulamadaha', 'Danau Tolire', 'Gunung Gamalama',
            'Hutan Lindung', 'Cagar Alam', 'Taman Nasional',
            'Pabrik Kelapa Sawit', 'Tambak Udang', 'Dermaga Nelayan',
            'Tower BTS', 'Gardu Listrik', 'PDAM', 'SPBU Pertamina'
        ];

        return $this->faker->randomElement($objek);
    }

    /**
     * Generate kecamatan names for Maluku Utara
     */
    private function generateKecamatan(): string
    {
        $kecamatans = [
            'Ternate Selatan', 'Ternate Tengah', 'Ternate Utara', 'Ternate Barat',
            'Pulau Ternate', 'Pulau Hiri', 'Moti', 'Tidore', 'Tidore Selatan',
            'Tidore Utara', 'Oba', 'Oba Utara', 'Oba Tengah', 'Oba Selatan',
            'Jailolo', 'Jailolo Selatan', 'Sahu', 'Sahu Timur', 'Ibu',
            'Ibu Utara', 'Ibu Selatan', 'Kao', 'Kao Utara', 'Kao Barat',
            'Malifut', 'Tobelo', 'Tobelo Selatan', 'Tobelo Utara', 'Tobelo Barat',
            'Tobelo Tengah', 'Galela', 'Galela Barat', 'Galela Selatan',
            'Galela Utara', 'Loloda', 'Loloda Utara', 'Morotai Selatan',
            'Morotai Selatan Barat', 'Morotai Utara', 'Morotai Timur'
        ];

        return $this->faker->randomElement($kecamatans);
    }

    /**
     * Generate kelurahan/desa names
     */
    private function generateKelurahan(): string
    {
        $kelurahans = [
            'Kampung Makassar', 'Kampung Pisang', 'Salahuddin', 'Kalumata',
            'Takoma', 'Tabam', 'Tobona', 'Kulaba', 'Sulamadaha', 'Batu Angus',
            'Dorpedu', 'Gambesi', 'Guraping', 'Tafure', 'Togafo', 'Rum',
            'Fitu', 'Akehuda', 'Tomalou', 'Bobane', 'Payahe', 'Kusuri',
            'Kupa-kupa', 'Luari', 'Dokoro', 'Sidangoli', 'Daruba', 'Gotowasi',
            'Loleba', 'Sangaji', 'Soasio', 'Gurabunga', 'Cobodoe', 'Bobaneigo',
            'Pilonga', 'Balisoan', 'Toloko', 'Popilo', 'Togono', 'Totodoku'
        ];

        return $this->faker->randomElement($kelurahans);
    }
}