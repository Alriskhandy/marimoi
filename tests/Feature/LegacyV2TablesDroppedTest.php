<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyV2TablesDroppedTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_v2_tables_are_dropped(): void
    {
        $this->assertFalse(Schema::hasTable('spatial_layers_legacy_v2'));
        $this->assertFalse(Schema::hasTable('spatial_layer_metadata_legacy_v2'));
        $this->assertFalse(Schema::hasTable('spatial_layer_features_legacy_v2'));
    }

    public function test_no_foreign_keys_point_to_legacy_v2_tables(): void
    {
        $foreignKeysToLegacy = DB::select(
            'select conname from pg_constraint where contype = ? and confrelid::regclass::text like ?',
            ['f', '%legacy_v2']
        );

        $this->assertSame([], $foreignKeysToLegacy);
    }
}
