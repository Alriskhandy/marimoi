<?php

namespace App\Providers;

use App\Models\DataSpatial;
use App\Models\LegacyCategory;
use App\Support\MapDataVersion;
use App\Support\SpatialFeaturesV3Sync;
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
        // Category (categories_v3/category_nodes sejak Fase 3 lanjutan) menulis
        // lewat raw DB::table() di dalam model, bukan Eloquent save()/delete()
        // biasa — event saved/deleted tidak pernah terpicu, jadi forget()-nya
        // dipanggil langsung di dalam Category::create()/update()/delete().
        // LegacyCategory (tabel `categories` lama) masih dipakai FrontendController/
        // DataSpatialController sampai direwrite ke v3 (Fase 4/5, belum selesai).
        DataSpatial::saved(fn () => MapDataVersion::forget());
        DataSpatial::deleted(fn () => MapDataVersion::forget());
        LegacyCategory::saved(fn () => MapDataVersion::forget());
        LegacyCategory::deleted(fn () => MapDataVersion::forget());

        // Jembatan Fase 4/5 (plan mellow-weaving-eclipse): DataSpatialController
        // sengaja belum direwrite ke v3, tapi /geojson publik sudah baca v3 —
        // setiap tulis ke data_spatial direplikasi real-time ke spatial_features_v3
        // supaya tidak ada data baru yang "hilang" dari peta publik.
        DataSpatial::created(fn (DataSpatial $d) => SpatialFeaturesV3Sync::upsert($d));
        DataSpatial::updated(fn (DataSpatial $d) => SpatialFeaturesV3Sync::upsert($d));
        DataSpatial::deleted(fn (DataSpatial $d) => SpatialFeaturesV3Sync::delete($d));

        RateLimiter::for('api-v1', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
