<?php

namespace Tests\Feature;

use App\Models\Aspirasi;
use App\Models\KategoriAspirasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Regresi untuk bug: FrontendController::aspirasiStore() dulu hardcode
 * kategori_aspirasi_id = 1 untuk jenis "kritik & saran", padahal ID kategori
 * "Kritik dan Saran" tidak dijamin selalu 1 (tergantung urutan seeding).
 */
class AspirasiKritikSaranKategoriTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'nama_pengirim' => 'Citra Lestari',
            'email' => 'citra@example.com',
            'phone' => '081211112222',
            'alamat' => 'Jl. Contoh No. 3',
            'jenis_aspirasi' => 'kritik & saran',
            'judul_aspirasi' => 'Saran layanan',
            'isi_aspirasi' => 'Mohon layanan lebih cepat direspons.',
            'agreement' => true,
            'h-captcha-response' => 'dummy-token',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Http::fake(['hcaptcha.com/*' => Http::response(['success' => true])]);
    }

    public function test_kritik_saran_uses_category_found_by_name_even_when_its_id_is_not_one(): void
    {
        // Kategori lain dibuat lebih dulu supaya "Kritik dan Saran" TIDAK dapat id=1,
        // membuktikan pencarian tidak lagi bergantung pada urutan auto-increment.
        KategoriAspirasi::create(['nama_kategori' => 'Infrastruktur']);
        KategoriAspirasi::create(['nama_kategori' => 'Kesehatan']);
        $kritikSaran = KategoriAspirasi::create(['nama_kategori' => 'Kritik dan Saran']);

        $this->assertNotSame(1, $kritikSaran->id, 'Prasyarat test tidak terpenuhi: kategori ini kebetulan dapat id 1.');

        $response = $this->postJson(route('aspirasi-masyarakat.store'), $this->payload(), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(201);

        $aspirasi = Aspirasi::where('nomor_tiket', $response->json('data.nomor_tiket'))->firstOrFail();
        $this->assertSame($kritikSaran->id, $aspirasi->kategori_aspirasi_id);
    }

    public function test_kritik_saran_fails_gracefully_when_default_category_is_missing(): void
    {
        // Sengaja tidak membuat kategori "Kritik dan Saran" sama sekali.
        KategoriAspirasi::create(['nama_kategori' => 'Infrastruktur']);

        $response = $this->postJson(route('aspirasi-masyarakat.store'), $this->payload(), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(500)
            ->assertJsonPath('status', 'error');
        $this->assertSame(0, Aspirasi::count());
    }
}
