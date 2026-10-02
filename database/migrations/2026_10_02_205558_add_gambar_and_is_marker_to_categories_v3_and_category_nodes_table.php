<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `gambar` (upload gambar kategori) dan `is_marker` (default ikon marker di
 * peta) adalah fitur nyata & teruji di Category v2 (dipakai aktif di
 * resources/views/backend/pages/categories/index.blade.php) yang terlewat
 * dari dokumen skema v3 — sama seperti deviasi `gambar`/`created_by` di
 * spatial_features_v3 (plan mellow-weaving-eclipse, Fase 3 lanjutan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories_v3', function (Blueprint $table) {
            $table->string('gambar')->nullable()->after('color');
            $table->boolean('is_marker')->default(false)->after('gambar');
        });

        Schema::table('category_nodes', function (Blueprint $table) {
            // icon/color juga tidak ada di category_nodes (hanya di categories_v3)
            // walau di v2 tiap level kategori (root/child/grandchild) bisa punya
            // icon/warna sendiri — ditambahkan di sini supaya tidak hilang.
            $table->string('icon', 100)->nullable()->after('description');
            $table->string('color', 9)->nullable()->after('icon');
            $table->string('gambar')->nullable()->after('color');
            $table->boolean('is_marker')->default(false)->after('gambar');
        });
    }

    public function down(): void
    {
        Schema::table('categories_v3', function (Blueprint $table) {
            $table->dropColumn(['gambar', 'is_marker']);
        });

        Schema::table('category_nodes', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color', 'gambar', 'is_marker']);
        });
    }
};
