<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Publication;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RolePermissionTest extends TestCase
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

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = $this->userFor($this->roleWith('admin-bappeda'));

        $this->actingAs($user)->get(route('aspirasi.index'))->assertForbidden();
    }

    public function test_user_with_permission_can_access_route(): void
    {
        $user = $this->userFor($this->roleWith('admin-bappeda', ['aspirasi.view']));

        $this->actingAs($user)->get(route('aspirasi.index'))->assertOk();
    }

    public function test_permission_is_specific_to_the_action(): void
    {
        $user = $this->userFor($this->roleWith('admin-bappeda', ['aspirasi.view']));

        $this->actingAs($user)->delete(route('aspirasi.bulk-destroy'))->assertForbidden();
    }

    public function test_super_admin_bypasses_permission_checks(): void
    {
        $user = $this->userFor($this->roleWith('super-admin'));

        $this->actingAs($user)->get(route('aspirasi.index'))->assertOk();
        $this->actingAs($user)->get(route('roles.index'))->assertOk();
    }

    public function test_changing_role_id_syncs_spatie_role(): void
    {
        $first = $this->roleWith('admin-bappeda');
        $second = $this->roleWith('admin-opd');
        $user = $this->userFor($first);

        $this->assertTrue($user->roles()->where('roles.id', $first->id)->exists());

        $user->update(['role_id' => $second->id]);

        $this->assertFalse($user->fresh()->roles()->where('roles.id', $first->id)->exists());
        $this->assertTrue($user->fresh()->roles()->where('roles.id', $second->id)->exists());
    }

    public function test_role_id_given_as_string_still_syncs_spatie_role(): void
    {
        $first = $this->roleWith('admin-bappeda');
        $second = $this->roleWith('admin-opd');
        $user = $this->userFor($first);

        $user->update(['role_id' => (string) $second->id]);

        $this->assertTrue($user->fresh()->roles()->where('roles.id', $second->id)->exists());
        $this->assertFalse($user->fresh()->roles()->where('roles.id', $first->id)->exists());
    }

    public function test_seeder_creates_catalog_and_default_permissions(): void
    {
        $superAdmin = $this->roleWith('super-admin');
        $opd = $this->roleWith('admin-opd');

        $this->seed(PermissionSeeder::class);

        $this->assertSame(count(Permission::catalogNames()), $superAdmin->permissions()->count());
        $this->assertTrue($opd->hasPermissionTo('aspirasi.view'));
        $this->assertFalse($opd->hasPermissionTo('users.delete'));
    }

    public function test_seeder_does_not_overwrite_customised_role(): void
    {
        $opd = $this->roleWith('admin-opd', ['logs.view']);

        $this->seed(PermissionSeeder::class);

        $this->assertSame(['logs.view'], $opd->permissions()->pluck('name')->all());
    }

    public function test_super_admin_can_load_and_sync_role_permissions(): void
    {
        $admin = $this->userFor($this->roleWith('super-admin'));
        $target = $this->roleWith('admin-opd');
        $this->seed(PermissionSeeder::class);
        $target->syncPermissions([]);

        $this->actingAs($admin)
            ->getJson(route('roles.permissions', $target))
            ->assertOk()
            ->assertJsonPath('data.locked', false);

        $this->actingAs($admin)
            ->putJson(route('roles.permissions.sync', $target), ['permissions' => ['aspirasi.view', 'opd.view']])
            ->assertOk();

        $this->assertEqualsCanonicalizing(['aspirasi.view', 'opd.view'], $target->fresh()->permissions()->pluck('name')->all());
    }

    public function test_sync_rejects_unknown_permission_and_locks_super_admin(): void
    {
        $superRole = $this->roleWith('super-admin');
        $admin = $this->userFor($superRole);
        $target = $this->roleWith('admin-opd');

        $this->actingAs($admin)
            ->putJson(route('roles.permissions.sync', $target), ['permissions' => ['bukan.permission']])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->putJson(route('roles.permissions.sync', $superRole), ['permissions' => []])
            ->assertForbidden();
    }

    public function test_role_without_roles_edit_cannot_sync_permissions(): void
    {
        $user = $this->userFor($this->roleWith('admin-bappeda', ['roles.view']));
        $target = $this->roleWith('admin-opd');

        $this->actingAs($user)
            ->putJson(route('roles.permissions.sync', $target), ['permissions' => []])
            ->assertForbidden();
    }

    public function test_sidebar_hides_menus_without_permission(): void
    {
        $user = $this->userFor($this->roleWith('admin-bappeda', ['dashboard.view', 'aspirasi.view']));

        $response = $this->actingAs($user)->get(route('aspirasi.index'));

        $response->assertOk();
        $response->assertSee('Data Aspirasi');
        $response->assertDontSee('Manajemen Pengguna');
        $response->assertDontSee('Log Sistem');
    }

    public function test_dokumen_buttons_follow_permissions(): void
    {
        $viewer = $this->userFor($this->roleWith('admin-bappeda', ['dokumen.view']));

        $this->actingAs($viewer)->get(route('dokumen.index'))
            ->assertOk()
            ->assertDontSee('data-bs-target="#addModal"', false);

        $editor = $this->userFor($this->roleWith('admin-opd', ['dokumen.view', 'dokumen.create']));

        $this->actingAs($editor)->get(route('dokumen.index'))
            ->assertOk()
            ->assertSee('data-bs-target="#addModal"', false);
    }

    public function test_log_action_buttons_require_manage_permission(): void
    {
        $viewer = $this->userFor($this->roleWith('admin-bappeda', ['logs.view']));

        $this->actingAs($viewer)->get(route('logs.index'))
            ->assertOk()
            ->assertDontSee(route('logs.prune-old'), false);

        $manager = $this->userFor($this->roleWith('admin-opd', ['logs.view', 'logs.manage']));

        $this->actingAs($manager)->get(route('logs.index'))
            ->assertOk()
            ->assertSee(route('logs.prune-old'), false);
    }

    public function test_super_admin_can_preview_publication_pdf_inline(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('dokumen_files/laporan.pdf', '%PDF-1.4 tes');

        $publication = Publication::create([
            'title' => 'Laporan Tahunan',
            'file_name' => 'Laporan Tahunan Ünggulan.pdf',
            'file_path' => 'dokumen_files/laporan.pdf',
            'file_type' => 'pdf',
            'file_size' => 12,
        ]);

        $response = $this->actingAs($this->userFor($this->roleWith('super-admin')))
            ->get(route('publications.preview', $publication));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_publication_preview_requires_permission_and_reports_missing_file(): void
    {
        Storage::fake('public');
        $publication = Publication::create([
            'title' => 'Hilang',
            'file_name' => 'hilang.pdf',
            'file_path' => 'dokumen_files/hilang.pdf',
            'file_type' => 'pdf',
            'file_size' => 1,
        ]);

        $this->actingAs($this->userFor($this->roleWith('admin-bappeda')))
            ->get(route('publications.preview', $publication))
            ->assertForbidden();

        $this->actingAs($this->userFor($this->roleWith('admin-opd', ['publications.view'])))
            ->get(route('publications.preview', $publication))
            ->assertNotFound();
    }
}
