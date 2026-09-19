<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FrontendPagesTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function publicPages(): array
    {
        return [
            'peta tematik' => ['tampil.tematik', false],
            'prioritas daerah' => ['tampil.prioritas', true],
            'publikasi' => ['tampil.publikasi', true],
            'aspirasi' => ['tampil.aspirasi', true],
            'profil reformer' => ['tampil.reformer', true],
            'kebijakan privasi' => ['kebijakan_privasi', true],
            'syarat ketentuan' => ['syarat_ketentuan', true],
            'tentang' => ['tampil.tentang', true],
            'faq' => ['tampil.faq', true],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_page_uses_the_shared_spatial_shell(string $route, bool $hasFooter): void
    {
        $response = $this->get(route($route));

        $response->assertOk();
        $response->assertSee('id="nav"', false);
        $response->assertSee('id="mobileMenu"', false);
        $response->assertDontSee('class="navbar"', false);

        if ($hasFooter) {
            $response->assertSee('Developed by', false);
        } else {
            $response->assertDontSee('Developed by', false);
        }
    }

    public function test_content_pages_show_the_page_hero_with_breadcrumb(): void
    {
        $this->get(route('tampil.publikasi'))
            ->assertOk()
            ->assertSee('Dokumen Publikasi')
            ->assertSee('Breadcrumb', false);
    }

    public function test_about_and_faq_pages_show_their_content(): void
    {
        $this->get(route('tampil.tentang'))
            ->assertOk()
            ->assertSee('Filosofi logo')
            ->assertSee('Kenali MARIMOI dalam video')
            ->assertSee('data-video-id="rxI6vk7dFGw"', false)
            ->assertSee('Enam prinsip')
            ->assertSee('Dukungan Terhadap MARIMOI')
            ->assertSee('data-video-id="cWA8hBj4PcE"', false)
            ->assertSee('id="videoModal"', false);

        $this->get(route('tampil.faq'))
            ->assertOk()
            ->assertSee('Apa itu MARIMOI?')
            ->assertSee('Bagaimana cara menyampaikan aspirasi?');
    }

    public function test_reformer_profile_shows_structured_history(): void
    {
        $this->get(route('tampil.reformer'))
            ->assertOk()
            ->assertSee('Riwayat pendidikan')
            ->assertSee('Riwayat jabatan')
            ->assertSee('data-cv-open', false)
            ->assertDontSee('Mangga Dua');
    }
}
