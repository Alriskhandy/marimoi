<?php

namespace Tests\Feature;

use App\Models\SpatialFeedback;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi untuk spatial_feedbacks (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 1.5) — CHECK constraint tepat satu
 * target (Layer ATAU Data Spasial, bukan keduanya/tidak ada).
 */
class SpatialFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private function layer(): SpatialLayer
    {
        return SpatialLayer::create(['slug' => 'layer-'.uniqid(), 'name' => 'Layer Uji', 'title' => 'Layer Uji', 'layer_class' => 'thematic']);
    }

    private function feature(SpatialLayer $layer): SpatialLayerFeature
    {
        return SpatialLayerFeature::create([
            'spatial_layer_id' => $layer->id,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
        ]);
    }

    public function test_feedback_targeting_layer_only_is_allowed(): void
    {
        $layer = $this->layer();

        $feedback = SpatialFeedback::create([
            'spatial_layer_id' => $layer->id,
            'nama_pemberi' => 'Warga Uji',
            'pesan' => 'Contoh pesan',
        ]);

        $this->assertNotNull($feedback->id);
    }

    public function test_feedback_targeting_data_spasial_only_is_allowed(): void
    {
        $layer = $this->layer();
        $feature = $this->feature($layer);

        $feedback = SpatialFeedback::create([
            'spatial_layer_feature_id' => $feature->id,
            'nama_pemberi' => 'Warga Uji',
            'pesan' => 'Contoh pesan',
        ]);

        $this->assertNotNull($feedback->id);
    }

    public function test_feedback_without_any_target_is_rejected_by_database(): void
    {
        $this->expectException(QueryException::class);

        SpatialFeedback::create(['nama_pemberi' => 'Warga Uji', 'pesan' => 'Contoh pesan']);
    }

    public function test_feedback_with_both_targets_is_rejected_by_database(): void
    {
        $layer = $this->layer();
        $feature = $this->feature($layer);

        $this->expectException(QueryException::class);

        SpatialFeedback::create([
            'spatial_layer_id' => $layer->id,
            'spatial_layer_feature_id' => $feature->id,
            'nama_pemberi' => 'Warga Uji',
            'pesan' => 'Contoh pesan',
        ]);
    }
}
