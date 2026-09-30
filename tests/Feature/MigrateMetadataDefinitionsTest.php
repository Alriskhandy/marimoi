<?php

namespace Tests\Feature;

use App\Models\MetadataDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk `marimoi:migrate-metadata-definitions` (docs/marimoi v2/03_plan/
 * 14-penyesuaian-database-jenis-peta.md Bagian 6 Tahap 3, Opsi B). 4 baris
 * is_system sudah di-seed langsung oleh migration `create_metadata_definitions_table`
 * — command ini sekarang idempotent safety-net, bukan satu-satunya jalan seed.
 */
class MigrateMetadataDefinitionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_change_data(): void
    {
        $before = MetadataDefinition::count();

        $this->artisan('marimoi:migrate-metadata-definitions', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame($before, MetadataDefinition::count());
    }

    public function test_command_is_idempotent_when_system_definitions_already_seeded(): void
    {
        $before = MetadataDefinition::where('is_system', true)->count();
        $this->assertSame(4, $before);

        $this->artisan('marimoi:migrate-metadata-definitions')->assertExitCode(0);

        $this->assertSame(4, MetadataDefinition::where('is_system', true)->count());
    }

    public function test_command_recreates_missing_system_definition(): void
    {
        MetadataDefinition::where('kode', 'pagu')->delete();
        $this->assertSame(3, MetadataDefinition::where('is_system', true)->count());

        $this->artisan('marimoi:migrate-metadata-definitions')->assertExitCode(0);

        $this->assertDatabaseHas('metadata_definitions', ['kode' => 'pagu', 'label' => 'Pagu', 'is_system' => true]);
    }
}
