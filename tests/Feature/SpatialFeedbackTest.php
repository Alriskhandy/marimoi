<?php

namespace Tests\Feature;

use App\Models\SpatialFeedback;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi untuk spatial_feedbacks (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 1.5) — CHECK constraint tepat satu
 * target (Layer ATAU Data Spasial, bukan keduanya/tidak ada).
 *
 * Fixture ditulis lewat DB::table() ke tabel v2 `spatial_layers`/
 * `spatial_layer_features` LANGSUNG — kolom `spatial_feedbacks.spatial_layer_id`/
 * `spatial_layer_feature_id` punya FK sungguhan ke tabel v2 tersebut (bigint),
 * BUKAN ke layers_v3/spatial_features_v3 (uuid/bigint-identity baru) yang
 * sejak Fase 3 (plan mellow-weaving-eclipse) jadi representasi Eloquent
 * `SpatialLayer`/`SpatialLayerFeature` — kedua hal itu sekarang tabel yang
 * berbeda sama sekali.
 */
class SpatialFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private function layerId(): int
    {
        return DB::table('spatial_layers_legacy_v2')->insertGetId([
            'public_id' => (string) Str::uuid(),
            'slug' => 'layer-'.Str::random(8),
            'name' => 'Layer Uji',
            'title' => 'Layer Uji',
            'visibility' => 'private',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function featureId(int $layerId): int
    {
        return DB::table('spatial_layer_features_legacy_v2')->insertGetId([
            'spatial_layer_id' => $layerId,
            'geometry' => DB::raw('ST_SetSRID(ST_MakePoint(127.5, 0.8), 4326)'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_feedback_targeting_layer_only_is_allowed(): void
    {
        $layerId = $this->layerId();

        $feedback = SpatialFeedback::create([
            'spatial_layer_id' => $layerId,
            'nama_pemberi' => 'Warga Uji',
            'pesan' => 'Contoh pesan',
        ]);

        $this->assertNotNull($feedback->id);
    }

    public function test_feedback_targeting_data_spasial_only_is_allowed(): void
    {
        $layerId = $this->layerId();
        $featureId = $this->featureId($layerId);

        $feedback = SpatialFeedback::create([
            'spatial_layer_feature_id' => $featureId,
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
        $layerId = $this->layerId();
        $featureId = $this->featureId($layerId);

        $this->expectException(QueryException::class);

        SpatialFeedback::create([
            'spatial_layer_id' => $layerId,
            'spatial_layer_feature_id' => $featureId,
            'nama_pemberi' => 'Warga Uji',
            'pesan' => 'Contoh pesan',
        ]);
    }
}
