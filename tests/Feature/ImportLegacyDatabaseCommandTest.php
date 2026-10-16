<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

/**
 * Database lama disimulasikan sebagai schema `legacy_src` di database test,
 * dibaca lewat koneksi `legacy_test` (search_path legacy_src). Koneksi itu
 * punya PDO sendiri, jadi datanya di-commit lalu schema dihapus di tearDown.
 */
class ImportLegacyDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.legacy_test' => array_merge(config('database.connections.pgsql'), ['search_path' => 'legacy_src,public'])]);

        $legacy = $this->legacy();
        $legacy->statement('DROP SCHEMA IF EXISTS legacy_src CASCADE');
        $legacy->statement('CREATE SCHEMA legacy_src');

        foreach ([
            'opd' => 'opd',
            'roles' => 'roles',
            'users' => 'users',
            'categories' => 'categories',
            'data_spatial' => 'data_spatial',
            'visitors' => 'visitors',
        ] as $table => $like) {
            $legacy->statement("CREATE TABLE legacy_src.{$table} (LIKE public.{$like})");
        }

        // Kolom yang di versi lama belum ada.
        $legacy->statement('ALTER TABLE legacy_src.roles DROP COLUMN guard_name');
        $legacy->statement('ALTER TABLE legacy_src.data_spatial DROP COLUMN sumber_data, DROP COLUMN opd_pengelola_id, DROP COLUMN tanggal_data');
    }

    protected function tearDown(): void
    {
        $this->legacy()->statement('DROP SCHEMA IF EXISTS legacy_src CASCADE');
        DB::purge('legacy_test');

        parent::tearDown();
    }

    private function legacy(): Connection
    {
        return DB::connection('legacy_test');
    }

    private function seedLegacyData(): void
    {
        $legacy = $this->legacy();

        $legacy->table('opd')->insert(['id' => 7, 'name' => 'Dinas PUPR', 'singkatan' => 'PUPR', 'is_active' => true]);

        $legacy->table('roles')->insert([
            ['id' => 1, 'name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true],
            ['id' => 3, 'name' => 'Admin OPD', 'slug' => 'admin-opd', 'is_active' => true],
        ]);

        $legacy->table('users')->insert([
            ['id' => 1, 'name' => 'Super Admin', 'email' => 'admin@example.com', 'password' => bcrypt('rahasia'), 'role_id' => 1, 'opd_id' => null, 'is_active' => true],
            ['id' => 19, 'name' => 'Admin PUPR', 'email' => 'pupr@example.com', 'password' => bcrypt('rahasia'), 'role_id' => 3, 'opd_id' => 7, 'is_active' => true],
        ]);

        // Induk (id 30) sengaja ber-ID lebih besar dari anaknya (id 10).
        $legacy->table('categories')->insert([
            ['id' => 30, 'type' => 'tematik', 'nama' => 'Infrastruktur', 'parent_id' => null, 'is_marker' => false, 'is_active' => true, 'warna' => null, 'icon' => null],
            ['id' => 10, 'type' => 'tematik', 'nama' => 'Jalan', 'parent_id' => 30, 'is_marker' => false, 'is_active' => true, 'warna' => '#ff0000', 'icon' => null],
            ['id' => 11, 'type' => 'tematik', 'nama' => 'Jalan Provinsi', 'parent_id' => 10, 'is_marker' => true, 'is_active' => true, 'warna' => '#00ff00', 'icon' => 'bi-geo'],
            ['id' => 12, 'type' => 'tematik', 'nama' => 'Arsip', 'parent_id' => 30, 'is_marker' => false, 'is_active' => false, 'warna' => null, 'icon' => null],
        ]);

        $legacy->table('data_spatial')->insert([
            ['id' => 100, 'user_id' => 19, 'data_type' => 'tematik', 'kategori_id' => 11, 'views' => 0, 'tahun' => 2024,
                'dbf_attributes' => json_encode(['NAMA' => 'Titik A']), 'geom' => DB::raw("ST_GeomFromText('POINT(127.3 0.8)', 4326)")],
            ['id' => 101, 'user_id' => 19, 'data_type' => 'tematik', 'kategori_id' => 11, 'views' => 0, 'tahun' => 2025,
                'dbf_attributes' => null, 'geom' => DB::raw("ST_GeomFromText('POINT Z(127.4 0.9 12)', 4326)")],
            ['id' => 102, 'user_id' => 1, 'data_type' => 'proyek_strategis', 'kategori_id' => 30, 'views' => 0, 'tahun' => 2026,
                'dbf_attributes' => json_encode(['PAKET' => 'Jembatan Halmahera', 'ANGGARAN' => 'Rp. 733.500.000,00']),
                'geom' => DB::raw("ST_GeomFromText('POLYGON((127 0, 127.1 0, 127.1 0.1, 127 0.1, 127 0))', 4326)")],
            ['id' => 103, 'user_id' => 1, 'data_type' => 'tematik', 'kategori_id' => 12, 'views' => 0, 'tahun' => null,
                'dbf_attributes' => null, 'geom' => DB::raw("ST_GeomFromText('LINESTRING(127 0, 127.2 0.2)', 4326)")],
            // Koordinat Web Mercator (meter) yang ditandai SRID 4326.
            ['id' => 104, 'user_id' => 1, 'data_type' => 'tematik', 'kategori_id' => 12, 'views' => 0, 'tahun' => null,
                'dbf_attributes' => null, 'geom' => DB::raw("ST_SetSRID(ST_Transform(ST_GeomFromText('POINT(127.5 0.5)', 4326), 3857), 4326)")],
        ]);
    }

    private function runImport(): PendingCommand
    {
        return $this->artisan('marimoi:import-legacy', ['--connection' => 'legacy_test', '--force' => true]);
    }

    public function test_copies_tables_with_original_ids_and_rebuilds_roles(): void
    {
        $this->seedLegacyData();

        $this->runImport()->assertSuccessful();

        $this->assertSame(['admin@example.com', 'pupr@example.com'], DB::table('users')->orderBy('id')->pluck('email')->all());
        $this->assertSame(19, DB::table('users')->where('email', 'pupr@example.com')->value('id'));
        $this->assertSame('super-admin', DB::table('roles')->where('id', 1)->value('slug'));
        $this->assertSame(['admin-bappeda', 'admin-opd', 'pimpinan', 'super-admin', 'user'], DB::table('roles')->orderBy('slug')->pluck('slug')->all());
        $this->assertSame(4, DB::table('categories')->count());
        $this->assertSame(10, DB::table('categories')->where('id', 11)->value('parent_id'));
        $this->assertSame(5, DB::table('data_spatial')->count());

        $admin = User::where('email', 'pupr@example.com')->first();
        $this->assertTrue($admin->hasRole('admin-opd'));
        $this->assertTrue(User::find(1)->isSuperAdmin());
        $this->assertTrue(DB::table('permissions')->count() > 3);

        $nextUser = User::factory()->create();
        $this->assertGreaterThan(19, $nextUser->id);
    }

    public function test_builds_category_tree_layers_and_features(): void
    {
        $this->seedLegacyData();

        $this->runImport()->assertSuccessful();

        $root = DB::table('categories_v3')->first();
        $this->assertSame('Infrastruktur', $root->name);
        $this->assertSame(1, DB::table('categories_v3')->count());

        $jalan = DB::table('category_nodes')->where('legacy_category_id', 10)->first();
        $provinsi = DB::table('category_nodes')->where('legacy_category_id', 11)->first();
        $this->assertSame(1, $jalan->depth);
        $this->assertNull($jalan->parent_id);
        $this->assertSame(2, $provinsi->depth);
        $this->assertSame($jalan->id, $provinsi->parent_id);
        $this->assertSame($jalan->path.'.n'.str_replace('-', '', $provinsi->id), $provinsi->path);

        // Satu Layer per kategori yang punya data: 11, 30 (root), 12 — bukan 10.
        $this->assertSame([11, 12, 30], DB::table('layers')->orderBy('legacy_category_id')->pluck('legacy_category_id')->all());

        $pointLayer = DB::table('layers')->where('legacy_category_id', 11)->first();
        $this->assertSame($root->id, $pointLayer->category_id);
        $this->assertSame($provinsi->id, $pointLayer->category_node_id);
        $this->assertSame('published', $pointLayer->status);
        $this->assertSame(1, (int) $pointLayer->layer_type_id);
        $this->assertSame(2, $pointLayer->feature_count);
        $this->assertNotNull($pointLayer->bbox);

        $style = DB::table('layer_styles')->where('id', $pointLayer->default_style_id)->first();
        $this->assertEquals(['color' => '#00ff00', 'icon' => 'bi-geo', 'is_marker' => true, 'opacity' => 1], json_decode($style->definition, true));
        $this->assertSame(2025, DB::table('layer_metadata')->where('layer_id', $pointLayer->id)->value('data_year'));

        $rootLayer = DB::table('layers')->where('legacy_category_id', 30)->first();
        $this->assertNull($rootLayer->category_node_id);
        $this->assertSame(3, (int) $rootLayer->layer_type_id);

        // Kategori nonaktif tetap tampil, sama seperti peta publik lama.
        $this->assertSame('published', DB::table('layers')->where('legacy_category_id', 12)->value('status'));

        $this->assertSame(5, DB::table('spatial_features')->count());

        $reprojected = DB::selectOne('SELECT ST_X(geom) AS x, ST_Y(geom) AS y FROM spatial_features WHERE legacy_data_spatial_id = 104');
        $this->assertEqualsWithDelta(127.5, $reprojected->x, 0.000001);
        $this->assertEqualsWithDelta(0.5, $reprojected->y, 0.000001);
        $feature = DB::table('spatial_features')->where('legacy_data_spatial_id', 100)->first();
        $this->assertEquals(['NAMA' => 'Titik A', 'tahun' => 2024], json_decode($feature->properties, true));
        $this->assertSame(19, $feature->created_by);
        $this->assertSame(2, (int) DB::selectOne('SELECT ST_NDims(geom) AS dims FROM spatial_features WHERE legacy_data_spatial_id = 101')->dims);
    }

    public function test_backfills_development_projects_from_proyek_strategis(): void
    {
        $this->seedLegacyData();

        $this->runImport()->assertSuccessful();

        $project = DB::table('development_projects')->first();
        $this->assertSame(1, DB::table('development_projects')->count());
        $this->assertSame('Jembatan Halmahera', $project->name);
        $this->assertSame('LEGACY-102', $project->project_code);
        $this->assertSame(733500000.0, (float) $project->budget_amount);
    }

    public function test_keeps_rows_with_duplicate_ids_under_a_new_id(): void
    {
        // Middleware pencatat pengunjung dari test lain bisa meninggalkan baris di sini.
        DB::table('visitors')->delete();
        $this->seedLegacyData();
        $visit = ['ip' => '180.243.32.76', 'page_visited' => '/', 'created_at' => now(), 'updated_at' => now()];
        $this->legacy()->table('visitors')->insert([
            ['id' => 1, 'city' => 'Ternate'] + $visit,
            ['id' => 1, 'city' => 'Manado'] + $visit,
            ['id' => 5, 'city' => 'Tidore'] + $visit,
        ]);

        $this->runImport()->assertSuccessful();

        $this->assertSame(3, DB::table('visitors')->count());
        $this->assertSame(['Ternate', 'Tidore', 'Manado'], DB::table('visitors')->orderBy('id')->pluck('city')->all());
        $this->assertSame([1, 5, 6], DB::table('visitors')->orderBy('id')->pluck('id')->all());
    }

    public function test_refuses_to_run_when_target_database_is_not_empty(): void
    {
        $this->seedLegacyData();
        User::factory()->create();

        $this->runImport()->assertFailed();

        $this->assertSame(0, DB::table('categories')->count());
    }

    public function test_fails_when_legacy_database_has_no_spatial_tables(): void
    {
        $this->legacy()->statement('DROP TABLE legacy_src.categories');

        $this->runImport()->assertFailed();
    }
}
