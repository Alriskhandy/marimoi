<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('legacyPages')]
    public function test_legacy_pages_redirect_to_peta_interaktif(string $path): void
    {
        $this->get($path)->assertRedirect('/peta-interaktif')->assertStatus(301);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function oldPetaTematikUrls(): array
    {
        return [
            'halaman peta' => ['/peta-tematik', '/peta-interaktif'],
            'link share' => ['/peta-tematik/share/AbCd1234', '/peta-interaktif/share/AbCd1234'],
            'detail fitur' => ['/peta-tematik/MARIMOI-123', '/peta-interaktif/MARIMOI-123'],
        ];
    }

    #[DataProvider('oldPetaTematikUrls')]
    public function test_old_peta_tematik_urls_redirect_permanently_to_peta_interaktif(string $oldPath, string $newPath): void
    {
        $this->get($oldPath)->assertStatus(301)->assertRedirect($newPath);
    }

    public function test_map_routes_use_interaktif_names(): void
    {
        foreach (['tampil.interaktif', 'post.interaktif', 'detail.interaktif', 'interaktif.share.store', 'interaktif.share.show', 'interaktif.version', 'interaktif.filter-options', 'interaktif.filter-categories'] as $name) {
            $this->assertTrue(Route::has($name), "route {$name} harus ada");
        }

        foreach (['tampil.tematik', 'post.tematik', 'detail.tematik', 'tematik.share.store', 'tematik.share.show', 'tematik.version'] as $name) {
            $this->assertFalse(Route::has($name), "route lama {$name} harus sudah tidak ada");
        }

        $this->assertSame(url('/peta-interaktif'), route('tampil.interaktif'));
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
