<?php

namespace App\Actions\Auth;

use App\Exceptions\Auth\GoogleLoginException;
use App\Models\Role;
use App\Models\User;
use App\Models\UserIdentity;
use App\Services\Auth\AuthenticationLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class HandleGoogleLogin
{
    public function __construct(private AuthenticationLogger $logger) {}

    /**
     * Resolve the local user for a Google Socialite user, linking or
     * provisioning the account as needed, and record the attempt.
     *
     * @throws GoogleLoginException
     */
    public function handle(SocialiteUser $googleUser, Request $request): User
    {
        if (empty($googleUser->getEmail())) {
            $this->logger->log(null, 'google', 'failed', false, $request, 'missing_email');

            throw new GoogleLoginException('Google tidak mengembalikan alamat email. Pastikan izin akses email diberikan.');
        }

        $user = $this->resolveUser($googleUser);

        if (! $user->is_active) {
            $this->logger->log($user, 'google', 'blocked', false, $request, 'account_disabled');

            throw new GoogleLoginException('Akun Anda telah dinonaktifkan. Hubungi Admin Sistem.', $user);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $this->logger->log($user, 'google', 'login', true, $request);

        return $user;
    }

    private function resolveUser(SocialiteUser $googleUser): User
    {
        $identity = UserIdentity::with('user')
            ->where('provider', 'google')
            ->where('provider_subject', $googleUser->getId())
            ->first();

        if ($identity) {
            $identity->fill([
                'provider_email' => $googleUser->getEmail(),
                'provider_data' => $this->providerData($googleUser),
                'last_used_at' => now(),
            ])->save();

            return $identity->user;
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getEmail(),
                'email' => $googleUser->getEmail(),
                'password' => null,
                'role_id' => Role::where('slug', 'publik')->value('id'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        try {
            UserIdentity::create([
                'user_id' => $user->id,
                'provider' => 'google',
                'provider_subject' => $googleUser->getId(),
                'provider_email' => $googleUser->getEmail(),
                'provider_data' => $this->providerData($googleUser),
                'last_used_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            $identity = UserIdentity::with('user')
                ->where('provider', 'google')
                ->where('provider_subject', $googleUser->getId())
                ->first();

            if ($identity) {
                return $identity->user;
            }

            throw new GoogleLoginException('Terjadi konflik saat menautkan akun Google. Coba lagi.');
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function providerData(SocialiteUser $googleUser): array
    {
        return array_filter([
            'picture' => $googleUser->getAvatar(),
            'nickname' => $googleUser->getNickname(),
        ]);
    }
}
