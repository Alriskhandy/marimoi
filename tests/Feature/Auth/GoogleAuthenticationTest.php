<?php

namespace Tests\Feature\Auth;

use App\Models\AuthenticationLog;
use App\Models\Role;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id, string $email, string $name = 'Warga Marimoi'): SocialiteUser
    {
        return (new SocialiteUser)->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => 'https://example.test/avatar.jpg',
        ]);
    }

    public function test_new_google_user_is_provisioned_with_user_role(): void
    {
        Role::create(['name' => 'User', 'slug' => 'user', 'description' => null]);
        Socialite::fake('google', $this->fakeGoogleUser('google-1', 'warga@example.com'));

        $response = $this->get('/login/google/callback');

        $this->assertAuthenticated();
        $response->assertRedirect(route('beranda', absolute: false));

        $user = User::where('email', 'warga@example.com')->firstOrFail();
        $this->assertTrue($user->isPublik());
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->last_login_at);

        $this->assertDatabaseHas('user_identities', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_subject' => 'google-1',
        ]);

        $this->assertDatabaseHas('authentication_logs', [
            'user_id' => $user->id,
            'provider' => 'google',
            'event' => 'login',
            'success' => true,
        ]);
    }

    public function test_existing_admin_email_is_linked_instead_of_creating_a_new_user(): void
    {
        $adminRole = Role::create(['name' => 'Admin Bappeda', 'slug' => 'admin-bappeda', 'description' => null]);
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role_id' => $adminRole->id,
        ]);
        Socialite::fake('google', $this->fakeGoogleUser('google-2', 'admin@example.com'));

        $response = $this->get('/login/google/callback');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($admin->fresh());

        $this->assertSame(1, User::where('email', 'admin@example.com')->count());
        $this->assertSame('admin-bappeda', $admin->fresh()->role->slug);
        $this->assertDatabaseHas('user_identities', [
            'user_id' => $admin->id,
            'provider_subject' => 'google-2',
        ]);
    }

    public function test_disabled_account_cannot_login_with_google(): void
    {
        $userRole = Role::create(['name' => 'User', 'slug' => 'user', 'description' => null]);
        $user = User::factory()->create([
            'email' => 'nonaktif@example.com',
            'role_id' => $userRole->id,
            'is_active' => false,
        ]);
        Socialite::fake('google', $this->fakeGoogleUser('google-3', 'nonaktif@example.com'));

        $response = $this->get('/login/google/callback');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('authentication_logs', [
            'user_id' => $user->id,
            'event' => 'blocked',
            'success' => false,
        ]);
    }

    public function test_returning_google_user_reuses_the_same_identity(): void
    {
        Role::create(['name' => 'User', 'slug' => 'user', 'description' => null]);
        Socialite::fake('google', $this->fakeGoogleUser('google-4', 'ulang@example.com'));

        $this->get('/login/google/callback');
        $this->post('/logout');

        Socialite::fake('google', $this->fakeGoogleUser('google-4', 'ulang@example.com'));
        $this->get('/login/google/callback');

        $this->assertSame(1, User::where('email', 'ulang@example.com')->count());
        $this->assertSame(1, UserIdentity::where('provider_subject', 'google-4')->count());
        $this->assertSame(2, AuthenticationLog::where('event', 'login')->count());
    }

    public function test_new_google_user_login_fails_clearly_when_user_role_is_missing(): void
    {
        // Tidak ada role 'user' yang dibuat, mensimulasikan database yang belum di-seed.
        Socialite::fake('google', $this->fakeGoogleUser('google-5', 'tanpa-role@example.com'));

        $response = $this->get('/login/google/callback');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('users', ['email' => 'tanpa-role@example.com']);
    }
}
