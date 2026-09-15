<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_guest_cannot_access_role_management(): void
    {
        $response = $this->get(route('roles.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_super_admin_cannot_access_role_management(): void
    {
        $role = Role::create(['name' => 'Admin Bappeda', 'slug' => 'admin-bappeda', 'description' => null]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get(route('roles.index'));

        $response->assertForbidden();
    }

    public function test_super_admin_can_view_role_index(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->get(route('roles.index'));

        $response->assertOk();
        $response->assertSee('Manajemen Role');
    }

    public function test_super_admin_can_create_role(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Admin Sektor',
            'slug' => 'admin-sektor',
            'description' => 'Role uji coba',
            'is_active' => 1,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('roles', ['slug' => 'admin-sektor', 'name' => 'Admin Sektor']);
    }

    public function test_role_creation_rejects_invalid_slug_format(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Role Tidak Valid',
            'slug' => 'Slug Tidak Valid!',
            'is_active' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('slug');
    }

    public function test_super_admin_can_update_role_but_slug_is_immutable(): void
    {
        $admin = $this->superAdmin();
        $role = Role::create(['name' => 'Admin Sektor', 'slug' => 'admin-sektor', 'description' => null]);

        $response = $this->actingAs($admin)->put(route('roles.update', $role), [
            'name' => 'Admin Sektor Baru',
            'slug' => 'slug-yang-dicoba-diubah',
            'description' => 'Diperbarui',
            'is_active' => 0,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Admin Sektor Baru',
            'slug' => 'admin-sektor',
            'is_active' => false,
        ]);
    }

    public function test_reserved_system_role_cannot_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $bappeda = Role::create(['name' => 'Admin Bappeda', 'slug' => 'admin-bappeda', 'description' => null]);

        $response = $this->actingAs($admin)->delete(route('roles.destroy', $bappeda));

        $response->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => $bappeda->id]);
    }

    public function test_role_still_used_by_users_cannot_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $role = Role::create(['name' => 'Admin Sektor', 'slug' => 'admin-sektor', 'description' => null]);
        User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($admin)->delete(route('roles.destroy', $role));

        $response->assertStatus(400);
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_unused_custom_role_can_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $role = Role::create(['name' => 'Admin Sektor', 'slug' => 'admin-sektor', 'description' => null]);

        $response = $this->actingAs($admin)->delete(route('roles.destroy', $role));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}
