<?php

namespace Tests\Feature;

use App\Models\MetadataDefinition;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk endpoint search MetadataDefinition (docs/marimoi v2/03_plan/
 * 14-penyesuaian-database-jenis-peta.md Bagian 6 Tahap 5.4) — dipakai UI form
 * Jenis Peta untuk "Pilih dari katalog", permission sama dengan map-types.manage
 * (Bagian 7 poin 7 — katalog belum punya halaman CRUD mandiri).
 */
class MetadataDefinitionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'map-types.manage', 'guard_name' => 'web']));

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_user_without_permission_cannot_search(): void
    {
        $user = User::factory()->create(['role_id' => Role::create(['name' => 'Admin OPD', 'slug' => 'admin-opd', 'description' => null])->id]);

        $this->actingAs($user)->getJson(route('metadata-definitions.search', ['q' => 'pagu']))->assertForbidden();
    }

    public function test_search_matches_kode_or_label(): void
    {
        $admin = $this->admin();
        MetadataDefinition::create(['kode' => 'lebar_jalan', 'label' => 'Lebar Jalan']);

        $response = $this->actingAs($admin)->getJson(route('metadata-definitions.search', ['q' => 'lebar']));

        $response->assertOk()->assertJsonFragment(['kode' => 'lebar_jalan']);
    }

    public function test_search_with_empty_query_returns_results_ordered_system_first(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->getJson(route('metadata-definitions.search'));

        $response->assertOk();
        $results = $response->json();
        $this->assertNotEmpty($results);
        $this->assertTrue($results[0]['is_system']);
    }

    public function test_search_finds_no_match_for_unrelated_query(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->getJson(route('metadata-definitions.search', ['q' => 'xyz-tidak-ada']));

        $response->assertOk()->assertJsonCount(0);
    }
}
