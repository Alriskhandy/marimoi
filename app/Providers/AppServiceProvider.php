<?php

namespace App\Providers;

use App\Models\DataSpatial;
use App\Models\DocumentTemplate;
use App\Models\LegacyCategory;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Support\MapDataVersion;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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

        // Template dokumen aktif untuk Unduh Peta & cetak Analisis (semua rute yang merender peta).
        View::composer('frontend.pages.peta', function ($view) {
            $view->with('documentTemplates', DocumentTemplate::publicList());
        });

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
        SpatialLayer::saved(fn () => MapDataVersion::forget());
        SpatialLayer::deleted(fn () => MapDataVersion::forget());
        SpatialLayerFeature::saved(fn () => MapDataVersion::forget());
        SpatialLayerFeature::deleted(fn () => MapDataVersion::forget());

        RateLimiter::for('api-v1', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
