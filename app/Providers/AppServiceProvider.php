<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nonaktifkan route Passport bawaan — kita daftarkan sendiri di routes/web.php
        // agar bisa menambahkan rate limit dan custom controller
        \Laravel\Passport\Passport::ignoreRoutes();
    }

    public function boot(): void
    {
        Paginator::useBootstrap();

        Passport::tokensCan([
            'public.read'        => 'Membaca data GIS publik, layer, dan fitur',
            'feedback.write'     => 'Mengirim aspirasi proyek',
            'admin.features'     => 'Kelola fitur GIS (CRUD)',
            'admin.layers'       => 'Kelola layer GIS (CRUD)',
            'admin.images'       => 'Kelola gambar fitur',
            'admin.users'        => 'Kelola pengguna dan peran',
            'admin.spatial'      => 'Kelola data spasial',
            'admin.dashboard'    => 'Akses analitik dashboard',
            'admin.publications' => 'Kelola publikasi',
            'admin.aspirations'  => 'Kelola aspirasi masyarakat',
        ]);

        // Default expiry (public) — admin di-override di AccessTokenController
        Passport::tokensExpireIn(now()->addDays(
            (int) env('PASSPORT_PUBLIC_TOKEN_EXPIRE_DAYS', 3)
        ));

        RateLimiter::for('oauth-token', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
