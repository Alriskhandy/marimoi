<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fitur "Feedback Pemetaan" (SpatialFeedbackController, route spatial-feedbacks.*,
     * permission spatial-feedbacks.*) dihapus 2026-10-14. Feedback masyarakat kini
     * satu pintu lewat `project_feedbacks` dan berlaku untuk semua data spasial.
     *
     * PermissionSeeder::RETIRED_PREFIXES juga memuat prefix ini sebagai jaring
     * kedua kalau seeder dijalankan di lingkungan yang belum kena migration.
     */
    public function up(): void
    {
        $permissionIds = DB::table('permissions')->where('name', 'like', 'spatial-feedbacks.%')->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        app()['cache']->forget('spatie.permission.cache');

        Schema::dropIfExists('spatial_feedbacks');
    }

    /**
     * Mengembalikan struktur tabel saja (FK mengikuti nama tabel v2 yang sudah
     * di-rename jadi *_legacy_v2). Permission dipulihkan oleh PermissionSeeder
     * setelah katalognya dikembalikan di kode.
     */
    public function down(): void
    {
        if (Schema::hasTable('spatial_feedbacks')) {
            return;
        }

        Schema::create('spatial_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spatial_layer_id')->nullable()->constrained('spatial_layers_legacy_v2')->cascadeOnDelete();
            $table->foreignId('spatial_layer_feature_id')->nullable()->constrained('spatial_layer_features_legacy_v2')->cascadeOnDelete();
            $table->string('nama_pemberi');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('pesan');
            $table->string('status', 20)->default('baru');
            $table->text('response_admin')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        DB::statement('
            ALTER TABLE spatial_feedbacks
            ADD CONSTRAINT spatial_feedbacks_target_check
            CHECK (
                (spatial_layer_id IS NOT NULL AND spatial_layer_feature_id IS NULL)
                OR (spatial_layer_id IS NULL AND spatial_layer_feature_id IS NOT NULL)
            )
        ');
    }
};
