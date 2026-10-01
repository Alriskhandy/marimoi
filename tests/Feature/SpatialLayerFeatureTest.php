<?php

namespace Tests\Feature;

use App\Models\Sector;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Models\SpatialLayerFeatureIntervention;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SpatialLayerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_sectors_are_seeded_from_migration(): void
    {
        $this->assertSame(5, Sector::count());
        $this->assertTrue(Sector::where('code', 'pupr')->exists());
    }

    public function test_spatial_layer_belongs_to_sector(): void
    {
        $pupr = Sector::where('code', 'pupr')->firstOrFail();
        $layer = SpatialLayer::create([
            'slug' => 'jalan-sektor', 'name' => 'Jalan', 'title' => 'Jalan',
            'sector_id' => $pupr->id,
        ]);

        $this->assertTrue($layer->sector->is($pupr));
        $this->assertTrue($pupr->spatialLayers->pluck('id')->contains($layer->id));
    }

    public function test_feature_belongs_to_layer_and_casts_attributes_as_array(): void
    {
        $layer = SpatialLayer::create(['slug' => 'jalan-fitur', 'name' => 'Jalan', 'title' => 'Jalan']);

        $feature = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'attributes' => ['kondisi' => 'baik'],
        ]);

        $this->assertTrue($feature->fresh()->layer->is($layer));
        $this->assertSame(['kondisi' => 'baik'], $feature->fresh()->attributes);
    }

    public function test_intervention_links_two_features_both_directions(): void
    {
        $layer = SpatialLayer::create(['slug' => 'jalan-intervensi', 'name' => 'Jalan', 'title' => 'Jalan']);

        $eksisting = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
        ]);
        $intervensi = SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.6, 0.9), 4326)'),
        ]);

        SpatialLayerFeatureIntervention::create([
            'feature_id_eksisting' => $eksisting->id,
            'feature_id_intervensi' => $intervensi->id,
            'jenis_hubungan' => 'peningkatan',
        ]);

        $this->assertTrue($eksisting->fresh()->intervensiTerkait->first()->intervensi->is($intervensi));
        $this->assertTrue($intervensi->fresh()->kondisiEksistingTerkait->first()->eksisting->is($eksisting));
    }

    public function test_intervention_pair_must_be_unique(): void
    {
        $layer = SpatialLayer::create(['slug' => 'jalan-unik', 'name' => 'Jalan', 'title' => 'Jalan']);
        $a = SpatialLayerFeature::create(['spatial_layer_id' => $layer->id, 'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)')]);
        $b = SpatialLayerFeature::create(['spatial_layer_id' => $layer->id, 'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.6, 0.9), 4326)')]);

        SpatialLayerFeatureIntervention::create(['feature_id_eksisting' => $a->id, 'feature_id_intervensi' => $b->id]);

        $this->expectException(QueryException::class);

        SpatialLayerFeatureIntervention::create(['feature_id_eksisting' => $a->id, 'feature_id_intervensi' => $b->id]);
    }
}
