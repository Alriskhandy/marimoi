<?php

namespace Tests\Feature;

use App\Models\MapType;
use App\Models\MapTypeDynamicAttribute;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk map_type_dynamic_attributes (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 1.2).
 */
class MapTypeDynamicAttributeTest extends TestCase
{
    use RefreshDatabase;

    private function mapType(): MapType
    {
        return MapType::create(['slug' => 'jenis-uji-'.uniqid(), 'nama' => 'Jenis Uji']);
    }

    public function test_placeholder_attribute_can_be_activated_for_a_map_type(): void
    {
        $jenis = $this->mapType();

        $attr = MapTypeDynamicAttribute::create([
            'map_type_id' => $jenis->id,
            'tipe' => MapTypeDynamicAttribute::TIPE_PLACEHOLDER,
            'kode_atribut' => 'pagu',
            'label' => 'Pagu',
            'satuan' => 'Rp',
            'is_wajib' => true,
        ]);

        $this->assertTrue($jenis->dynamicAttributes()->where('id', $attr->id)->exists());
        $this->assertTrue($attr->is_wajib);
    }

    public function test_custom_attribute_can_be_added(): void
    {
        $jenis = $this->mapType();

        $attr = MapTypeDynamicAttribute::create([
            'map_type_id' => $jenis->id,
            'tipe' => MapTypeDynamicAttribute::TIPE_CUSTOM,
            'kode_atribut' => 'lebar_jalan',
            'label' => 'Lebar Jalan',
            'satuan' => 'meter',
            'is_wajib' => false,
        ]);

        $this->assertSame('custom', $attr->tipe);
        $this->assertFalse($attr->is_wajib);
    }

    public function test_kode_atribut_must_be_unique_per_jenis(): void
    {
        $jenis = $this->mapType();
        MapTypeDynamicAttribute::create([
            'map_type_id' => $jenis->id, 'tipe' => 'placeholder', 'kode_atribut' => 'pagu', 'label' => 'Pagu',
        ]);

        $this->expectException(QueryException::class);

        MapTypeDynamicAttribute::create([
            'map_type_id' => $jenis->id, 'tipe' => 'placeholder', 'kode_atribut' => 'pagu', 'label' => 'Pagu Lagi',
        ]);
    }

    public function test_same_kode_atribut_allowed_across_different_jenis(): void
    {
        $jenisA = $this->mapType();
        $jenisB = $this->mapType();

        MapTypeDynamicAttribute::create(['map_type_id' => $jenisA->id, 'tipe' => 'placeholder', 'kode_atribut' => 'pagu', 'label' => 'Pagu']);
        $attrB = MapTypeDynamicAttribute::create(['map_type_id' => $jenisB->id, 'tipe' => 'placeholder', 'kode_atribut' => 'pagu', 'label' => 'Pagu']);

        $this->assertNotNull($attrB->id);
    }

    public function test_inactive_attribute_does_not_disappear_but_is_flagged(): void
    {
        $jenis = $this->mapType();
        $attr = MapTypeDynamicAttribute::create([
            'map_type_id' => $jenis->id, 'tipe' => 'placeholder', 'kode_atribut' => 'status', 'label' => 'Status', 'is_active' => false,
        ]);

        $this->assertFalse($attr->fresh()->is_active);
        $this->assertTrue($jenis->dynamicAttributes()->where('id', $attr->id)->exists());
    }
}
