<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\ProjectFeedback;
use App\Models\ProyekStrategisDaerah;

class FeedbackStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Membuat proyek dummy
        ProyekStrategisDaerah::factory()->create(['id' => 1]);
    }

    public function test_invalid_project_type_returns_400()
    {
        $response = $this->postJson(route('feedback.store'), [
            'project_type' => 'unknown_type'
        ]);

        $response->assertStatus(400)
                 ->assertJson(['status' => 'error', 'message' => 'Invalid project type']);
    }

    public function test_validation_errors_for_missing_fields()
    {
        $response = $this->postJson(route('feedback.store'), [
            'project_type' => 'proyek_strategis_daerah'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'feedbackable_id', 'nama_pemberi_aspirasi',
                     'jenis_tanggapan', 'tanggapan'
                 ]);
    }

    public function test_keluhan_requires_image()
    {
        $response = $this->postJson(route('feedback.store'), [
            'project_type' => 'proyek_strategis_daerah',
            'feedbackable_id' => 1,
            'nama_pemberi_aspirasi' => 'Budi',
            'jenis_tanggapan' => 'keluhan',
            'tanggapan' => 'Keluhan tanpa gambar',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['laporan_gambar']);
    }

    public function test_successful_feedback_without_image()
    {
        $payload = [
            'project_type' => 'proyek_strategis_daerah',
            'feedbackable_id' => 1,
            'nama_pemberi_aspirasi' => 'Siti',
            'jenis_tanggapan' => 'saran',
            'tanggapan' => 'Beberapa saran',
        ];

        $response = $this->postJson(route('feedback.store'), $payload);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success', 'message' => 'Tanggapan berhasil ditambahkan'])
                 ->assertJsonStructure(['data' => ['id', 'feedbackable_id', 'status']]);

        $this->assertDatabaseHas('project_feedbacks', [
            'feedbackable_id' => 1,
            'nama_pemberi_aspirasi' => 'Siti',
            'jenis_tanggapan' => 'saran',
            'status' => 'pending'
        ]);
    }

    public function test_successful_keluhan_with_image()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('keluhan.jpg');

        $payload = [
            'project_type' => 'proyek_strategis_daerah',
            'feedbackable_id' => 1,
            'nama_pemberi_aspirasi' => 'Anton',
            'jenis_tanggapan' => 'keluhan',
            'tanggapan' => 'Keluhan dengan gambar',
            'laporan_gambar' => $file,
        ];

        $response = $this->postJson(route('feedback.store'), $payload);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        // Pastikan file tersimpan
        Storage::disk('public')->assertExists('feedback_images/' . $response->json('data.laporan_gambar'));
        $this->assertDatabaseHas('project_feedbacks', [
            'feedbackable_id' => 1,
            'nama_pemberi_aspirasi' => 'Anton',
            'jenis_tanggapan' => 'keluhan',
            'status' => 'pending'
        ]);
    }
}
