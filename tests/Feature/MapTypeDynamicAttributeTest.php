<?php

namespace Tests\Feature;

use App\Models\MapType;
use App\Models\MapTypeDynamicAttribute;
use App\Models\MetadataDefinition;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk map_type_dynamic_attributes + metadata_definitions
 * (docs/marimoi v2/03_plan/14-penyesuaian-database-jenis-peta.md Bagian 6, Opsi
 * B) — map_type_dynamic_attributes jadi pivot murni ke katalog global
 * MetadataDefinition, bukan penyimpan definisi (label/satuan) langsung lagi.
 */
class MapTypeDynamicAttributeTest extends TestCase
{
    use RefreshDatabase;

    private function mapType(): MapType
    {
        return MapType::create(['slug' => 'jenis-uji-'.uniqid(), 'nama' => 'Jenis Uji']);
    }

    private function definition(array $overrides = []): MetadataDefinition
    {
        return MetadataDefinition::create(array_merge([
            'kode' => 'kode-uji-'.uniqid(),
            'label' => 'Label Uji',
        ], $overrides));
    }

    public function test_system_definition_can_be_activated_for_a_map_type(): void
    {
        $jenis = $this->mapType();
        // 'pagu' sudah di-seed migration create_metadata_definitions_table (4 baris
        // is_system bawaan) — pakai yang sudah ada, bukan create() baru (bentrok
        // unique kode).
        $pagu = MetadataDefinition::where('kode', 'pagu')->where('is_system', true)->firstOrFail();

        $attr = MapTypeDynamicAttribute::create([
            'map_type_id' => $jenis->id,
            'metadata_definition_id' => $pagu->id,
            'is_wajib' => true,
        ]);

        $this->assertTrue($jenis->dynamicAttributes()->where('id', $attr->id)->exists());
        $this->assertTrue($attr->is_wajib);
        $this->assertSame('pagu', $attr->metadataDefinition->kode);
    }

    public function test_custom_definition_can_be_created_and_linked(): void
    {
        $jenis = $this->mapType();
        $lebarJalan = $this->definition(['kode' => 'lebar_jalan', 'label' => 'Lebar Jalan', 'satuan' => 'meter']);

        $attr = MapTypeDynamicAttribute::create([
            'map_type_id' => $jenis->id,
            'metadata_definition_id' => $lebarJalan->id,
            'is_wajib' => false,
        ]);

        $this->assertFalse($lebarJalan->fresh()->is_system);
        $this->assertFalse($attr->is_wajib);
        $this->assertSame('Lebar Jalan', $attr->metadataDefinition->label);
    }

    public function test_metadata_definition_id_must_be_unique_per_jenis(): void
    {
        $jenis = $this->mapType();
        $definition = $this->definition();

        MapTypeDynamicAttribute::create(['map_type_id' => $jenis->id, 'metadata_definition_id' => $definition->id]);

        $this->expectException(QueryException::class);

        MapTypeDynamicAttribute::create(['map_type_id' => $jenis->id, 'metadata_definition_id' => $definition->id]);
    }

    /**
     * Regresi Opsi B: berbeda dari perilaku lama (`test_same_kode_atribut_allowed_across_different_jenis`,
     * di mana tiap Jenis boleh punya definisi TERPISAH dengan kode yang kebetulan
     * sama) — sekarang dua Jenis yang pakai atribut "sama" berbagi SATU baris
     * `metadata_definitions` yang sama persis, bukan duplikat.
     */
    public function test_same_metadata_definition_reused_across_different_jenis(): void
    {
        $jenisA = $this->mapType();
        $jenisB = $this->mapType();
        $definition = $this->definition();

        $attrA = MapTypeDynamicAttribute::create(['map_type_id' => $jenisA->id, 'metadata_definition_id' => $definition->id]);
        $attrB = MapTypeDynamicAttribute::create(['map_type_id' => $jenisB->id, 'metadata_definition_id' => $definition->id]);

        $this->assertNotNull($attrB->id);
        $this->assertSame($attrA->metadata_definition_id, $attrB->metadata_definition_id);
        $this->assertSame(1, MetadataDefinition::where('kode', $definition->kode)->count());
    }

    public function test_inactive_attribute_does_not_disappear_but_is_flagged(): void
    {
        $jenis = $this->mapType();
        $definition = $this->definition();

        $attr = MapTypeDynamicAttribute::create([
            'map_type_id' => $jenis->id,
            'metadata_definition_id' => $definition->id,
            'is_active' => false,
        ]);

        $this->assertFalse($attr->fresh()->is_active);
        $this->assertTrue($jenis->dynamicAttributes()->where('id', $attr->id)->exists());
    }
}
