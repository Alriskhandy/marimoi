<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Support\MapDataVersion;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrap();

        // Super Admin selalu lolos seluruh pengecekan permission.
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Data/kategori Peta Tematik berubah: buang versi peta di server agar cache browser
        // pengunjung segera dinyatakan usang (lihat App\Support\MapDataVersion).
        DataSpatial::saved(fn () => MapDataVersion::forget());
        DataSpatial::deleted(fn () => MapDataVersion::forget());
        Category::saved(fn () => MapDataVersion::forget());
        Category::deleted(fn () => MapDataVersion::forget());

        RateLimiter::for('api-v1', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
