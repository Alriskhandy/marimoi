<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

class BackendUnusedViewsRemovedTest extends TestCase
{
    public function test_unused_backend_views_are_removed(): void
    {
        foreach ([
            'backend.index',
            'backend.pages.peta',
            'backend.pages.spatial-layers._form',
            'backend.pages.data_spatial.index',
            'backend.pages.data_spatial.map',
            'backend.pages.data_spatial.create',
            'backend.pages.data_spatial.edit',
            'backend.pages.data_spatial._detail_modal',
            'backend.pages.data_spatial._map_layer_checklist',
            'backend.partials.kategori_option',
        ] as $view) {
            $this->assertFalse(View::exists($view), "View {$view} seharusnya sudah dihapus.");
        }
    }

    public function test_shared_backend_views_still_exist(): void
    {
        foreach ([
            'backend.partials.main',
            'backend.pages.spatial-layers._category-picker',
            'backend.pages.spatial-layers._wizard-step-1-info',
            'backend.pages.spatial-layers.index',
            'backend.pages.categories.index',
        ] as $view) {
            $this->assertTrue(View::exists($view), "View {$view} masih dipakai dan tidak boleh hilang.");
        }
    }
}
