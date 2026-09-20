<?php

namespace Tests\Feature\DataSpatial;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetadataDatasetTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function adminOpd(Opd $opd, array $permissions = []): User
    {
        $role = Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null]);

        foreach ($permissions as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return User::factory()->create(['role_id' => $role->id, 'opd_id' => $opd->id]);
    }

    private function category(): Category
    {
        return Category::create(['type' => 'tematik', 'nama' => 'Fasilitas Uji', 'warna' => '#0d6efd']);
    }

    private function coordinatePayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'data_type' => 'tematik',
            'kategori_id' => $category->id,
            'input_type' => 'coordinates',
            'coordinates' => [
                ['latitude' => 0.79, 'longitude' => 127.38, 'name' => 'Titik Uji'],
            ],
        ], $overrides);
    }

    public function test_admin_opd_creating_data_gets_own_opd_as_pengelola_automatically(): void
    {
        $opd = Opd::create(['name' => 'Dinas Uji Coba', 'singkatan' => 'DUC']);
        $admin = $this->adminOpd($opd, ['data-spatial.create']);
        $category = $this->category();

        $this->actingAs($admin)
            ->post(route('data-spatial.store'), $this->coordinatePayload($category))
            ->assertRedirect();

        $data = DataSpatial::firstOrFail();
        $this->assertSame($opd->id, $data->opd_pengelola_id);
    }

    public function test_super_admin_can_set_opd_pengelola_explicitly(): void
    {
        $admin = $this->superAdmin();
        $opd = Opd::create(['name' => 'Dinas Lain', 'singkatan' => 'DLN']);
        $category = $this->category();

        $this->actingAs($admin)
            ->post(route('data-spatial.store'), $this->coordinatePayload($category, [
                'opd_pengelola_id' => $opd->id,
                'sumber_data' => 'Survei lapangan 2025',
                'tanggal_data' => '2025-06-01',
            ]))
            ->assertRedirect();

        $data = DataSpatial::firstOrFail();
        $this->assertSame($opd->id, $data->opd_pengelola_id);
        $this->assertSame('Survei lapangan 2025', $data->sumber_data);
        $this->assertSame('2025-06-01', $data->tanggal_data->format('Y-m-d'));
    }

    public function test_store_rejects_invalid_opd_pengelola_id(): void
    {
        $admin = $this->superAdmin();
        $category = $this->category();

        $this->actingAs($admin)
            ->post(route('data-spatial.store'), $this->coordinatePayload($category, [
                'opd_pengelola_id' => 999999,
            ]))
            ->assertSessionHasErrors('opd_pengelola_id');

        $this->assertSame(0, DataSpatial::count());
    }

    public function test_updating_data_persists_metadata_fields(): void
    {
        $admin = $this->superAdmin();
        $opd = Opd::create(['name' => 'Dinas Update', 'singkatan' => 'DUP']);
        $category = $this->category();
        $data = DataSpatial::factory()->create([
            'user_id' => $admin->id,
            'kategori_id' => $category->id,
        ]);

        $this->actingAs($admin)
            ->put(route('data-spatial.update', $data->id), [
                'kategori_id' => $category->id,
                'deskripsi' => $data->deskripsi,
                'sumber_data' => 'SK Gubernur No. 123',
                'opd_pengelola_id' => $opd->id,
                'tanggal_data' => '2025-01-15',
            ])
            ->assertRedirect();

        $data->refresh();
        $this->assertSame('SK Gubernur No. 123', $data->sumber_data);
        $this->assertSame($opd->id, $data->opd_pengelola_id);
        $this->assertSame('2025-01-15', $data->tanggal_data->format('Y-m-d'));
    }

    public function test_admin_opd_cannot_move_data_to_another_opd_on_update(): void
    {
        $ownOpd = Opd::create(['name' => 'Dinas Sendiri', 'singkatan' => 'DSD']);
        $otherOpd = Opd::create(['name' => 'Dinas Lain 2', 'singkatan' => 'DL2']);
        $admin = $this->adminOpd($ownOpd, ['data-spatial.edit']);
        $category = $this->category();
        $data = DataSpatial::factory()->create([
            'user_id' => $admin->id,
            'kategori_id' => $category->id,
        ]);

        $this->actingAs($admin)
            ->put(route('data-spatial.update', $data->id), [
                'kategori_id' => $category->id,
                'deskripsi' => $data->deskripsi,
                'opd_pengelola_id' => $otherOpd->id,
            ])
            ->assertRedirect();

        $data->refresh();
        $this->assertSame($ownOpd->id, $data->opd_pengelola_id);
    }

    public function test_metadata_lengkap_accessor_reflects_completeness(): void
    {
        $user = User::factory()->create();
        $category = $this->category();
        $opd = Opd::create(['name' => 'Dinas Lengkap', 'singkatan' => 'DLK']);

        $incomplete = DataSpatial::factory()->create(['user_id' => $user->id, 'kategori_id' => $category->id]);
        $this->assertFalse($incomplete->metadata_lengkap);

        $complete = DataSpatial::factory()->create([
            'user_id' => $user->id,
            'kategori_id' => $category->id,
            'sumber_data' => 'Survei lapangan',
            'opd_pengelola_id' => $opd->id,
            'tanggal_data' => now(),
        ]);
        $this->assertTrue($complete->metadata_lengkap);
    }
}
