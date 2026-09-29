<?php

namespace Tests\Feature;

use App\Models\MapType;
use App\Models\Opd;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpatialLayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function roleWith(string $slug, array $permissions = []): Role
    {
        $role = Role::create(['name' => ucfirst($slug), 'slug' => $slug, 'description' => null]);

        foreach ($permissions as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $role;
    }

    private function userFor(Role $role): User
    {
        return User::factory()->create(['role_id' => $role->id]);
    }

    /**
     * Metadata Utama (Bagian 3.1 docs/marimoi v2/04_implementation/
     * 12-implementasi-perbaikan-pemetaan.md) wajib diisi saat store/update — payload
     * dasar yang valid, dipakai berulang di test lain lewat array_merge.
     */
    private function validMetadataUtama(): array
    {
        $opd = Opd::create(['name' => 'Dinas Uji '.uniqid(), 'singkatan' => 'DU']);

        return [
            'sumber_data' => 'Dinas Uji',
            'opd_penanggung_jawab_id' => $opd->id,
            'tanggal_data' => '2026-01-01',
        ];
    }

    public function test_user_without_permission_cannot_access_map_types(): void
    {
        $user = $this->userFor($this->roleWith('admin-bappeda'));

        $this->actingAs($user)->get(route('map-types.index'))->assertForbidden();
    }

    /**
     * Regresi: _form.blade.php sempat gagal kompilasi (direktif @json() Blade tidak
     * menangani argumen array literal multi-baris dengan benar, hasil kompilasi
     * terpotong jadi PHP tidak valid) — TIDAK pernah ketahuan dari test store()/
     * update() manapun karena tidak ada yang benar-benar GET & render halaman ini.
     * Wajib ada test yang benar-benar merender create/edit, bukan cuma POST/PUT.
     */
    public function test_create_page_renders_without_compile_error(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        $this->actingAs($admin)->get(route('map-types.create'))->assertOk();
    }

    public function test_edit_page_renders_without_compile_error_and_includes_existing_dynamic_attribute(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();
        $mapType->dynamicAttributes()->create(['tipe' => 'placeholder', 'kode_atribut' => 'pagu', 'label' => 'Pagu']);

        $this->actingAs($admin)->get(route('map-types.edit', $mapType))->assertOk();
    }

    public function test_super_admin_can_manage_map_types(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        $this->actingAs($admin)->get(route('map-types.index'))->assertOk();

        $response = $this->actingAs($admin)->post(route('map-types.store'), array_merge($this->validMetadataUtama(), [
            'slug' => 'rawan_bencana',
            'nama' => 'Kawasan Rawan Bencana',
            'urutan' => 6,
        ]));
        $response->assertRedirect();

        $this->assertDatabaseHas('map_types', ['slug' => 'rawan_bencana', 'nama' => 'Kawasan Rawan Bencana']);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        // 'tematik' sudah ada dari seed migration create_map_types_table.
        $this->actingAs($admin)
            ->post(route('map-types.store'), array_merge($this->validMetadataUtama(), ['slug' => 'tematik', 'nama' => 'Duplikat']))
            ->assertSessionHasErrors('slug');
    }

    public function test_store_rejects_submission_without_metadata_utama(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        $this->actingAs($admin)
            ->post(route('map-types.store'), ['slug' => 'tanpa_metadata', 'nama' => 'Tanpa Metadata'])
            ->assertSessionHasErrors(['sumber_data', 'opd_penanggung_jawab_id', 'tanggal_data']);

        $this->assertDatabaseMissing('map_types', ['slug' => 'tanpa_metadata']);
    }

    public function test_update_changes_map_type_fields(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('map-types.update', $mapType), array_merge($this->validMetadataUtama(), [
                'slug' => 'tematik', 'nama' => 'Peta Tematik Baru',
            ]))
            ->assertRedirect(route('map-types.edit', $mapType));

        $this->assertDatabaseHas('map_types', ['id' => $mapType->id, 'nama' => 'Peta Tematik Baru']);
    }

    public function test_update_persists_dynamic_attributes(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();

        $this->actingAs($admin)->put(route('map-types.update', $mapType), array_merge($this->validMetadataUtama(), [
            'slug' => 'tematik',
            'nama' => $mapType->nama,
            'dynamic_attributes' => [
                ['tipe' => 'placeholder', 'kode_atribut' => 'pagu', 'label' => 'Pagu', 'satuan' => 'Rp', 'is_wajib' => '1'],
            ],
        ]));

        $this->assertDatabaseHas('map_type_dynamic_attributes', [
            'map_type_id' => $mapType->id,
            'kode_atribut' => 'pagu',
            'is_wajib' => true,
        ]);
    }

    public function test_removing_a_dynamic_attribute_row_deletes_it(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();
        $mapType->dynamicAttributes()->create(['tipe' => 'placeholder', 'kode_atribut' => 'pagu', 'label' => 'Pagu']);

        $this->actingAs($admin)->put(route('map-types.update', $mapType), array_merge($this->validMetadataUtama(), [
            'slug' => 'tematik',
            'nama' => $mapType->nama,
            'dynamic_attributes' => [],
        ]));

        $this->assertDatabaseMissing('map_type_dynamic_attributes', ['map_type_id' => $mapType->id, 'kode_atribut' => 'pagu']);
    }

    public function test_destroy_blocks_deletion_when_still_used_by_a_layer(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();
        SpatialLayer::create(['slug' => 'jalan', 'name' => 'Jalan', 'title' => 'Jalan', 'layer_class' => 'thematic', 'map_type_id' => $mapType->id]);

        $this->actingAs($admin)
            ->delete(route('map-types.destroy', $mapType))
            ->assertRedirect();

        $this->assertDatabaseHas('map_types', ['id' => $mapType->id]);
    }

    public function test_destroy_succeeds_when_not_used(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::create(['slug' => 'kosong', 'nama' => 'Kosong']);

        $this->actingAs($admin)
            ->delete(route('map-types.destroy', $mapType))
            ->assertRedirect(route('map-types.index'));

        $this->assertDatabaseMissing('map_types', ['id' => $mapType->id]);
    }

    /**
     * Regresi: index dulu memakai edit modal yang form action-nya sempat salah
     * (url() bukan route(), mengabaikan prefix 'dashboard'). Sekarang edit pindah
     * ke halaman penuh (bukan modal) — pastikan link edit di index mengarah ke
     * route yang benar (dengan prefix dashboard), bukan URL yang ditulis manual.
     */
    public function test_index_page_edit_link_uses_correct_dashboard_prefixed_route(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('map-types.index'));

        $response->assertOk();
        $response->assertSee(route('map-types.edit', $mapType), false);
    }

    public function test_activating_a_previously_inactive_map_type_via_update_route_succeeds(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'psd')->firstOrFail();
        $this->assertFalse($mapType->is_active);

        $this->actingAs($admin)
            ->put(route('map-types.update', $mapType), array_merge($this->validMetadataUtama(), [
                'slug' => 'psd',
                'nama' => $mapType->nama,
                'is_active' => '1',
            ]))
            ->assertRedirect(route('map-types.edit', $mapType));

        $this->assertDatabaseHas('map_types', ['id' => $mapType->id, 'is_active' => true]);
    }
}
