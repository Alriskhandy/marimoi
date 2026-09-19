<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LegacyMapPagesRedirectTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function legacyPages(): array
    {
        return [
            'proyek strategis daerah' => ['/proyek-strategis-daerah'],
            'proyek strategis nasional' => ['/proyek-strategis-nasional'],
            'pokir dprd' => ['/pokir-dprd'],
            'usulan musrenbang' => ['/usulan-musrenbang'],
        ];
    }

    /**
     * @dataProvider legacyPages
     */
    public function test_legacy_pages_redirect_to_peta_tematik(string $path): void
    {
        $this->get($path)->assertRedirect('/peta-tematik')->assertStatus(301);
    }

    public function test_navigation_no_longer_lists_removed_features(): void
    {
        $html = $this->get(route('beranda'))->getContent();

        foreach (['tampil.psd', 'tampil.psn', 'tampil.pokir', 'tampil.musrenbang'] as $routeName) {
            $this->assertFalse(Route::has($routeName));
        }

        $this->assertStringNotContainsString('href="'.url('/pokir-dprd').'"', $html);
        $this->assertStringNotContainsString('href="'.url('/usulan-musrenbang').'"', $html);
        $this->assertStringNotContainsString('href="'.url('/proyek-strategis-daerah').'"', $html);
        $this->assertStringNotContainsString('href="'.url('/proyek-strategis-nasional').'"', $html);
    }
}
