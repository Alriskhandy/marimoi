<?php

namespace Tests\Feature;

use App\Models\AdministrativeRegion;
use App\Models\DevelopmentProject;
use App\Models\ProjectProgressReport;
use App\Models\Publication;
use App\Models\PublicationDownload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UnusedRelationTablesDroppedTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_relation_tables_are_dropped(): void
    {
        foreach ([
            'project_locations',
            'user_identities',
            'user_role_assignments',
            'project_progress_report_revisions',
            'spatial_layer_regions',
            'spatial_layer_feature_interventions',
            'surveys',
        ] as $table) {
            $this->assertFalse(Schema::hasTable($table), "Tabel {$table} seharusnya sudah di-drop.");
        }
    }

    public function test_dead_relations_are_removed_from_models(): void
    {
        $this->assertFalse(method_exists(DevelopmentProject::class, 'locations'));
        $this->assertFalse(method_exists(User::class, 'identities'));
        $this->assertFalse(method_exists(User::class, 'roleAssignments'));
        $this->assertFalse(method_exists(ProjectProgressReport::class, 'revisions'));
        $this->assertFalse(method_exists(AdministrativeRegion::class, 'spatialLayers'));
        $this->assertFalse(method_exists(Publication::class, 'surveys'));
        $this->assertFalse(method_exists(PublicationDownload::class, 'survey'));
        $this->assertFalse(class_exists('App\\Models\\Survey'));
        $this->assertFalse(class_exists('App\\Http\\Controllers\\SurveyController'));
    }

    public function test_kept_tables_still_exist(): void
    {
        $this->assertTrue(Schema::hasTable('development_projects'));
        $this->assertTrue(Schema::hasTable('project_regions'));
        $this->assertTrue(Schema::hasTable('project_progress_reports'));
        $this->assertTrue(Schema::hasTable('administrative_regions'));
        $this->assertTrue(Schema::hasTable('publications'));
        $this->assertTrue(Schema::hasTable('publication_downloads'));
    }
}
