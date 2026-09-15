<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendNavbarAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_button_on_navbar(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('login'), false);
        $response->assertDontSee('id="logout-form"', false);
    }

    public function test_login_page_has_google_login_button(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee(route('login.google'), false);
    }

    public function test_authenticated_public_user_sees_profile_menu_and_logout(): void
    {
        $role = Role::create(['name' => 'User', 'slug' => 'user', 'description' => null]);
        $user = User::factory()->create(['name' => 'Warga Marimoi', 'role_id' => $role->id]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Warga Marimoi');
        $response->assertSee(route('logout'), false);
        $response->assertDontSee(route('login.google'), false);
        $response->assertDontSee(route('dashboard'), false);
    }

    public function test_authenticated_admin_sees_dashboard_link_in_profile_menu(): void
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => null]);
        $user = User::factory()->create(['name' => 'Admin Sistem', 'role_id' => $role->id]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee(route('dashboard'), false);
    }
}
