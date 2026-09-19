<?php

namespace Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class LoginPageTest extends TestCase
{
    public function test_login_page_uses_the_spatial_design_without_bootstrap(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('id="loginForm"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="remember"', false);
        $response->assertSee(route('login.google'), false);
        $response->assertSee('h-captcha', false);
        $response->assertDontSee('bootstrap', false);
    }

    public function test_login_page_shows_validation_errors(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag([
            'email' => 'Email atau password salah.',
            'h-captcha-response' => 'CAPTCHA tidak valid.',
        ]));

        $this->withSession(['errors' => $errors])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Email atau password salah.')
            ->assertSee('CAPTCHA tidak valid.');
    }
}
