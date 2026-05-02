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
        Schema::table('layers', function (Blueprint $table) {
            $table->enum('category', ['tematik', 'psd', 'psn', 'musrenbang', 'pokir'])->after('type');

            // Tambah index untuk category jika belum ada
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layers', function (Blueprint $table) {
            $table->dropIndex(['category']);

            $table->dropColumn('category');
        });
    }
};
