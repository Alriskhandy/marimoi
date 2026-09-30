<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fitur "Kelola Peta" (maps.*) dihapus seluruhnya — digantikan oleh Jenis Peta
     * (map_types) & Layer (spatial_layers). Tabel-tabel ini murni milik fitur lama
     * yang dihapus, tidak ada data yang perlu dipertahankan.
     */
    public function up(): void
    {
        Schema::dropIfExists('map_share_accesses');
        Schema::dropIfExists('map_shares');
        Schema::dropIfExists('map_publications');
        Schema::dropIfExists('map_layers');
        Schema::dropIfExists('map_layer_groups');
        Schema::dropIfExists('maps');
    }

    public function down(): void
    {
        Schema::create('maps', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_opd_id')->nullable()->constrained('opd')->nullOnDelete();
            $table->string('visibility', 20)->default('private');
            $table->boolean('is_active')->default(true);
            $table->jsonb('basemap_config')->nullable();
            $table->geometry('center_point')->nullable();
            $table->decimal('zoom', 5, 2)->nullable();
            $table->geometry('max_extent')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('map_layer_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained('maps')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('map_layer_groups')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('display_order')->default(0);
            $table->jsonb('properties')->nullable();
            $table->timestamps();
        });

        Schema::create('map_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained('maps')->cascadeOnDelete();
            $table->foreignId('spatial_layer_id')->constrained('spatial_layers')->restrictOnDelete();
            $table->foreignId('layer_group_id')->nullable()->constrained('map_layer_groups')->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('display_name')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->decimal('opacity', 4, 3)->default(1);
            $table->jsonb('style_config')->nullable();
            $table->jsonb('filter_config')->nullable();
            $table->jsonb('chart_config')->nullable();
            $table->smallInteger('min_zoom')->nullable();
            $table->smallInteger('max_zoom')->nullable();
            $table->timestamps();

            $table->unique(['map_id', 'spatial_layer_id']);
            $table->index(['map_id', 'display_order']);
        });

        Schema::create('map_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained('maps')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->jsonb('config_snapshot');
            $table->jsonb('layer_version_snapshot')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at');
            $table->timestamp('unpublished_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique(['map_id', 'revision']);
        });

        Schema::create('map_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_publication_id')->constrained('map_publications')->cascadeOnDelete();
            $table->string('token_hash', 128)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->bigInteger('access_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->string('qr_path')->nullable();
            $table->timestamps();
        });

        Schema::create('map_share_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_share_id')->constrained('map_shares')->cascadeOnDelete();
            $table->timestamp('accessed_at');
            $table->string('ip_hash', 128)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referer')->nullable();
            $table->smallInteger('response_status')->nullable();

            $table->index(['map_share_id', 'accessed_at']);
        });
    }
};
