<?php

namespace Tests\Feature;

use App\Models\DataSpatial;
use App\Models\KategoriAspirasi;
use App\Models\LegacyCategory as Category;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * hCaptcha di form Aspirasi & Feedback proyek dilewati saat APP_ENV=local, tetap wajib di environment lain.
 */
class AspirasiCaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // CSRF hanya otomatis dilewati di env "testing"; test ini mengganti env.
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Mail::fake();
        Http::fake();
        KategoriAspirasi::create(['nama_kategori' => 'Kritik dan Saran']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadWithoutCaptcha(): array
    {
        return [
            'nama_pengirim' => 'Dewi Anggraini',
            'email' => 'dewi@example.com',
            'phone' => '081277778888',
            'alamat' => 'Jl. Contoh No. 4',
            'jenis_aspirasi' => 'kritik & saran',
            'judul_aspirasi' => 'Saran layanan',
            'isi_aspirasi' => 'Mohon jam layanan diperpanjang.',
            'agreement' => true,
        ];
    }

    public function test_local_environment_accepts_aspirasi_without_captcha(): void
    {
        $this->app['env'] = 'local';

        $this->postJson(route('aspirasi-masyarakat.store'), $this->payloadWithoutCaptcha())
            ->assertCreated();

        Http::assertNothingSent();
        $this->assertDatabaseHas('aspirasi', ['email' => 'dewi@example.com']);
    }

    public function test_local_environment_hides_captcha_widget(): void
    {
        $this->app['env'] = 'local';

        $this->get(route('tampil.aspirasi'))
            ->assertOk()
            ->assertDontSee('js.hcaptcha.com', false)
            ->assertDontSee('class="h-captcha"', false)
            ->assertSee('Mode development: hCaptcha dinonaktifkan.');
    }

    public function test_production_environment_still_requires_captcha(): void
    {
        $this->app['env'] = 'production';

        $this->postJson(route('aspirasi-masyarakat.store'), $this->payloadWithoutCaptcha())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['h-captcha-response']);

        $this->get(route('tampil.aspirasi'))
            ->assertOk()
            ->assertSee('js.hcaptcha.com', false)
            ->assertSee('class="h-captcha"', false)
            ->assertDontSee('Mode development');
    }

    private function dataSpatial(): DataSpatial
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Kategori Uji', 'warna' => '#0d6efd']);

        return DataSpatial::factory()->create([
            'user_id' => User::factory()->create()->id,
            'kategori_id' => $category->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function feedbackPayloadWithoutCaptcha(DataSpatial $dataSpatial): array
    {
        return [
            'data_spatial_id' => $dataSpatial->id,
            'nama_pemberi_aspirasi' => 'Rahmat Hidayat',
            'email' => 'rahmat@example.com',
            'phone' => '081233334444',
            'nama_proyek' => 'Jalan Lingkar Ternate',
            'kabupaten_kota' => 'Ternate',
            'jenis_tanggapan' => 'saran',
            'tanggapan' => 'Mohon ditambah lampu jalan.',
        ];
    }

    public function test_local_environment_accepts_feedback_without_captcha(): void
    {
        $this->app['env'] = 'local';

        $this->postJson(route('feedback.store'), $this->feedbackPayloadWithoutCaptcha($this->dataSpatial()))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        Http::assertNothingSent();
        $this->assertDatabaseHas('project_feedbacks', ['email' => 'rahmat@example.com']);
    }

    public function test_local_environment_hides_captcha_widget_on_feedback_form(): void
    {
        $dataSpatial = $this->dataSpatial();
        $this->app['env'] = 'local';

        $this->get(route('detail.interaktif', $dataSpatial->uuid))
            ->assertOk()
            ->assertDontSee('js.hcaptcha.com', false)
            ->assertDontSee('class="h-captcha"', false)
            ->assertSee('Mode development: hCaptcha dinonaktifkan.');
    }

    public function test_production_environment_still_requires_captcha_on_feedback(): void
    {
        $dataSpatial = $this->dataSpatial();
        $this->app['env'] = 'production';

        $this->postJson(route('feedback.store'), $this->feedbackPayloadWithoutCaptcha($dataSpatial))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['h-captcha-response']);

        $this->get(route('detail.interaktif', $dataSpatial->uuid))
            ->assertOk()
            ->assertSee('js.hcaptcha.com', false)
            ->assertSee('class="h-captcha"', false)
            ->assertDontSee('Mode development');
    }
}
