<?php

namespace Tests\Feature;

use App\Models\MapType;
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

    public function test_user_without_permission_cannot_access_map_types(): void
    {
        $user = $this->userFor($this->roleWith('admin-bappeda'));

        $this->actingAs($user)->get(route('map-types.index'))->assertForbidden();
    }

    public function test_super_admin_can_manage_map_types(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        $this->actingAs($admin)->get(route('map-types.index'))->assertOk();

        $this->actingAs($admin)->post(route('map-types.store'), [
            'slug' => 'rawan_bencana',
            'nama' => 'Kawasan Rawan Bencana',
            'urutan' => 6,
        ])->assertRedirect(route('map-types.index'));

        $this->assertDatabaseHas('map_types', ['slug' => 'rawan_bencana', 'nama' => 'Kawasan Rawan Bencana']);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));

        // 'tematik' sudah ada dari seed migration create_map_types_table.
        $this->actingAs($admin)
            ->post(route('map-types.store'), ['slug' => 'tematik', 'nama' => 'Duplikat'])
            ->assertSessionHasErrors('slug');
    }

    public function test_update_changes_map_type_fields(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('map-types.update', $mapType), ['slug' => 'tematik', 'nama' => 'Peta Tematik Baru'])
            ->assertRedirect(route('map-types.index'));

        $this->assertDatabaseHas('map_types', ['id' => $mapType->id, 'nama' => 'Peta Tematik Baru']);
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
     * Regresi: edit modal sempat membangun form action lewat url('map-types') yang
     * mengabaikan prefix route 'dashboard', menghasilkan submit ke /map-types/{id}
     * (404) alih-alih dashboard/map-types/{id} yang sebenarnya terdaftar.
     */
    public function test_index_page_renders_edit_form_action_with_correct_dashboard_prefix(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'tematik')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('map-types.index'));

        $response->assertOk();
        $response->assertSee(route('map-types.update', ':id'), false);
        $response->assertDontSee("'".url('map-types')."'", false);
    }

    public function test_activating_a_previously_inactive_map_type_via_update_route_succeeds(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin', ['map-types.manage']));
        $mapType = MapType::where('slug', 'psd')->firstOrFail();
        $this->assertFalse($mapType->is_active);

        $this->actingAs($admin)
            ->put(route('map-types.update', $mapType), [
                'slug' => 'psd',
                'nama' => $mapType->nama,
                'is_active' => '1',
            ])
            ->assertRedirect(route('map-types.index'));

        $this->assertDatabaseHas('map_types', ['id' => $mapType->id, 'is_active' => true]);
    }
}
