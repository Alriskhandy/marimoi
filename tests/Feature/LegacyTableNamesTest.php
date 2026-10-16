<?php

namespace Tests\Feature;

use App\Models\DataSpatial;
use App\Models\LegacyCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyTableNamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_tables_use_the_same_names_as_the_old_database(): void
    {
        $this->assertTrue(Schema::hasTable('categories'));
        $this->assertTrue(Schema::hasTable('data_spatial'));
        $this->assertFalse(Schema::hasTable('categories_legacy_v1'));
        $this->assertFalse(Schema::hasTable('data_spatial_legacy_v1'));

        $this->assertSame('categories', (new LegacyCategory)->getTable());
        $this->assertSame('data_spatial', (new DataSpatial)->getTable());
    }
}
