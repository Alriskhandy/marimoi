<?php

namespace Database\Factories;

use App\Models\DocumentTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentTemplate>
 */
class DocumentTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Template '.fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'header_enabled' => true,
            'header_line1' => 'PEMERINTAH PROVINSI MALUKU UTARA',
            'header_line2' => 'BADAN PERENCANAAN PEMBANGUNAN DAERAH',
            'header_line3' => fake()->address(),
            'accent_color' => '#1d3557',
            'orientation' => 'landscape',
            'layout' => null,
            'footer_text' => 'Sumber data: MARIMOI',
            'for_map' => true,
            'for_analysis' => true,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
        ];
    }

    public function portrait(): static
    {
        return $this->state(fn () => ['orientation' => 'portrait']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function asDefault(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }

    public function withoutHeader(): static
    {
        return $this->state(fn () => ['header_enabled' => false]);
    }
}
