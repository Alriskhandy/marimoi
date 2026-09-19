<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_homepage_renders_spatial_storytelling_with_real_data(): void
    {
        $response = $this->get(route('beranda'));

        $response->assertOk();
        $response->assertSee('Memetakan Masa Depan', false);
        $response->assertSee('id="heroNodes"', false);
        $response->assertSee('id="homeMap"', false);
        $response->assertSee('data-parallax', false);
        $response->assertSee('id="flowCanvas"', false);
        $this->assertSame(6, substr_count($response->getContent(), 'data-panel data-on'));
        $response->assertSee('mockup/tab-mockup.webp', false);
        $response->assertSee('window.MARIMOI_HOME', false);
        $response->assertSee('Developed by', false);
        $response->assertDontSee('bootstrap', false);
        $response->assertDontSee('class="btn', false);
        $response->assertDontSee('class="container', false);

        foreach (['tampil.tematik', 'tampil.prioritas', 'tampil.aspirasi', 'tampil.publikasi'] as $name) {
            $response->assertSee('href="'.route($name).'"', false);
        }
    }
}
