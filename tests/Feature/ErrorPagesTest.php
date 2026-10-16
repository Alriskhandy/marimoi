<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function errorPages(): array
    {
        return [
            '403' => [403, 'Akses Ditolak'],
            '404' => [404, 'Halaman Tidak Ditemukan'],
            '419' => [419, 'Sesi Kedaluwarsa'],
            '500' => [500, 'Kesalahan Server'],
            '503' => [503, 'Sedang Dalam Pemeliharaan'],
        ];
    }

    #[DataProvider('errorPages')]
    public function test_error_page_renders_with_title(int $code, string $title): void
    {
        $html = view('errors.'.$code)->render();

        $this->assertStringContainsString((string) $code, $html);
        $this->assertStringContainsString($title, $html);
        $this->assertStringContainsString('Kembali ke Beranda', $html);
    }

    public function test_unknown_route_renders_custom_404(): void
    {
        $this->get('/halaman-yang-tidak-ada-xyz')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan');
    }
}
