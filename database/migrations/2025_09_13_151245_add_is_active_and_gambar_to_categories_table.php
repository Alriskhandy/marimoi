<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Tambah kolom is_active setelah is_marker
            $table->boolean('is_active')->default(false)->after('is_marker');

            // Tambah kolom gambar setelah icon
            $table->string('gambar', 255)->nullable()->after('icon');

            // Tambah index untuk is_active jika belum ada
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop index terlebih dahulu
            $table->dropIndex(['is_active']);

            // Drop kolom
            $table->dropColumn(['is_active', 'gambar']);
        });
    }
};
