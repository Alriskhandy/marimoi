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
        Schema::table('layers', function (Blueprint $table) {
            $table->smallInteger('wizard_step')->nullable();
        });

        DB::statement('ALTER TABLE layers ADD CONSTRAINT ck_layers_wizard_step CHECK (wizard_step IS NULL OR wizard_step BETWEEN 1 AND 4)');
        DB::statement('CREATE INDEX ix_layers_wizard_step ON layers (wizard_step) WHERE wizard_step IS NOT NULL AND deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ix_layers_wizard_step');
        DB::statement('ALTER TABLE layers DROP CONSTRAINT IF EXISTS ck_layers_wizard_step');

        Schema::table('layers', function (Blueprint $table) {
            $table->dropColumn('wizard_step');
        });
    }
};
