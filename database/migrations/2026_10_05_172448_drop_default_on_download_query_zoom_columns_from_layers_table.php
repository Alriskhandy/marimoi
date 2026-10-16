<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE layers DROP CONSTRAINT IF EXISTS ck_layers_v3_zoom');

        Schema::table('layers', function (Blueprint $table) {
            $table->dropColumn(['is_default_on', 'is_downloadable', 'is_queryable', 'min_zoom', 'max_zoom']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layers', function (Blueprint $table) {
            $table->boolean('is_default_on')->default(false);
            $table->boolean('is_downloadable')->default(false);
            $table->boolean('is_queryable')->default(true);
            $table->smallInteger('min_zoom')->nullable();
            $table->smallInteger('max_zoom')->nullable();
        });

        DB::statement('ALTER TABLE layers ADD CONSTRAINT ck_layers_v3_zoom CHECK (min_zoom IS NULL OR max_zoom IS NULL OR min_zoom <= max_zoom)');
    }
};
