<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\HandleGoogleLogin;
use App\Exceptions\Auth\GoogleLoginException;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthenticationLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, HandleGoogleLogin $handleGoogleLogin, AuthenticationLogger $logger): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            $logger->log(null, 'google', 'failed', false, $request, 'invalid_state');

            return redirect()->route('login')
                ->withErrors(['email' => 'Sesi login Google kedaluwarsa, silakan coba lagi.']);
        }

        try {
            $user = $handleGoogleLogin->handle($googleUser, $request);
        } catch (GoogleLoginException $e) {
            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }

        auth()->login($user, remember: true);
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return redirect()->intended(route('beranda', absolute: false));
    }
}
