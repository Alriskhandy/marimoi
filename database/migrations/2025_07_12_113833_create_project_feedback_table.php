<?php

// database/migrations/2024_01_01_000000_create_project_feedbacks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pastikan ekstensi PostGIS sudah aktif (jika menggunakan spatial data)
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        } catch (\Exception $e) {
            // Jika PostGIS tidak tersedia, lanjutkan tanpa spatial support
        }

        Schema::create('project_feedbacks', function (Blueprint $table) {
            $table->id();
            
            // Basic feedback information
            $table->string('nama_pemberi_aspirasi');
            $table->string('nama_proyek');
            $table->string('kabupaten_kota');
            $table->string('kecamatan')->nullable();
            
            // Location coordinates (regular decimal columns)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Feedback content
            $table->string('laporan_gambar')->nullable();
            $table->text('tanggapan');
            $table->enum('jenis_tanggapan', ['keluhan', 'saran', 'apresiasi', 'pertanyaan'])->default('saran');
            $table->enum('status', ['pending', 'ditinjau', 'ditindaklanjuti', 'selesai'])->default('pending');
            
            // Contact information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            
            // Admin response
            $table->text('response_admin')->nullable();
            $table->timestamp('responded_at')->nullable();
            
            // Polymorphic relationship columns
            $table->unsignedBigInteger('feedbackable_id');
            $table->string('feedbackable_type');
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['feedbackable_id', 'feedbackable_type'], 'feedbackable_index');
            $table->index(['kabupaten_kota', 'status']);
            $table->index(['jenis_tanggapan', 'status']);
            $table->index(['created_at']);
            $table->index(['status']);
            $table->index(['latitude', 'longitude']);
        });

        // Add spatial column if PostGIS is available
        try {
            DB::statement('ALTER TABLE project_feedbacks ADD COLUMN geom GEOMETRY(POINT, 4326)');
            DB::statement('CREATE INDEX idx_project_feedbacks_geom ON project_feedbacks USING GIST (geom)');
        } catch (\Exception $e) {
            // PostGIS not available, skip spatial support
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_feedbacks');
    }
};
