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
        Schema::table('data_spatial', function (Blueprint $table) {
            $table->string('sumber_data')->nullable()->after('deskripsi');
            $table->foreignId('opd_pengelola_id')->nullable()->after('sumber_data')
                ->constrained('opd')->nullOnDelete();
            $table->date('tanggal_data')->nullable()->after('opd_pengelola_id');

            $table->index('opd_pengelola_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_spatial', function (Blueprint $table) {
            $table->dropForeign(['opd_pengelola_id']);
            $table->dropColumn(['sumber_data', 'opd_pengelola_id', 'tanggal_data']);
        });
    }
};
