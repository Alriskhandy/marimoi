<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

class FrontendUnusedViewsRemovedTest extends TestCase
{
    public function test_unused_frontend_views_are_removed(): void
    {
        foreach ([
            'frontend.layouts.app',
            'frontend.layouts.auth',
            'frontend.layouts.dark',
            'frontend.layouts.main',
            'frontend.pages.aspirasi-backup',
            'frontend.pages.index',
            'frontend.pages.index-dark',
            'frontend.pages.index-section.about',
            'frontend.pages.index-section.aspirasi',
            'frontend.pages.index-section.aspirasi-backup',
            'frontend.pages.index-section.dukungan',
            'frontend.pages.index-section.faq',
            'frontend.pages.index-section.filosofi',
            'frontend.pages.index-section.fitur-utama',
            'frontend.pages.index-section.hero',
            'frontend.pages.index-section.indikator-pembangunan',
            'frontend.pages.index-section.layanan-utama',
            'frontend.pages.index-section.logo-section',
            'frontend.pages.index-section.peta-tematik',
            'frontend.pages.index-section.running-text',
            'frontend.pages.index2',
            'frontend.pages.peta-backup',
            'frontend.partials.footer',
            'frontend.partials.footer-dark-tailwind',
            'frontend.partials.footer2',
            'frontend.partials.hero',
            'frontend.partials.nav-map',
            'frontend.partials.navbar',
        ] as $view) {
            $this->assertFalse(View::exists($view), "View {$view} seharusnya sudah dihapus.");
        }
    }

    public function test_rendered_frontend_views_still_exist(): void
    {
        foreach ([
            'frontend.layouts.spatial',
            'frontend.pages.home',
            'frontend.pages.peta',
            'frontend.pages.detail',
            'frontend.pages.aspirasi',
            'frontend.pages.publikasi',
            'frontend.partials.spatial-nav',
            'frontend.partials.spatial-footer',
        ] as $view) {
            $this->assertTrue(View::exists($view), "View {$view} masih dirender dan tidak boleh hilang.");
        }
    }
}
