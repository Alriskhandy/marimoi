<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

class UnusedViewsRemovedTest extends TestCase
{
    public function test_unused_views_outside_backend_and_frontend_are_removed(): void
    {
        foreach ([
            'auth.login',
            'auth.register',
            'coming_soon',
            'components.application-logo',
            'components.danger-button',
            'components.dropdown',
            'components.dropdown-link',
            'components.modal',
            'components.nav-link',
            'components.responsive-nav-link',
            'components.secondary-button',
            'emails.aspirasi_mail',
            'emails.tanggapan',
            'layouts.app',
            'layouts.navigation',
            'lokasi.index',
            'lokasi.input',
            'lokasi.peta',
            'profile.partials.delete-user-form',
            'profile.partials.update-password-form',
            'profile.partials.update-profile-information-form',
            'webgis',
            'welcome',
        ] as $view) {
            $this->assertFalse(View::exists($view), "View {$view} seharusnya sudah dihapus.");
        }
    }

    public function test_rendered_views_still_exist(): void
    {
        foreach ([
            'auth.login2',
            'auth.forgot-password',
            'auth.reset-password',
            'dashboard',
            'profile.edit',
            'layouts.guest',
            'components.text-input',
            'components.primary-button',
            'emails.notification',
            'emails.notification-text',
            'errors.404',
        ] as $view) {
            $this->assertTrue(View::exists($view), "View {$view} masih dirender dan tidak boleh hilang.");
        }
    }
}
