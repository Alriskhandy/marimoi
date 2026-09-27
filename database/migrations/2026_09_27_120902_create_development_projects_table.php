<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Hasil verifikasi cardinality (lihat docs/marimoi v2/04_implementation/
     * 09-implementasi-penuh-database-v2.md Prioritas 5): 121 baris data_spatial
     * data_type='proyek_strategis', 53 nilai PAKET berbeda, TIDAK ADA PAKET yang
     * dipakai lebih dari 1 baris — cardinality "1 baris = 1 proyek" terkonfirmasi.
     * Tapi 39 dari 121 baris (32%) tidak punya PAKET, dan opd_pengelola_id/URUSAN
     * tidak bisa dipetakan otomatis dengan aman ke opd.id — makanya owner_opd_id
     * dan sector_id nullable + ada kolom needs_review, BUKAN ditebak.
     */
    public function up(): void
    {
        Schema::create('development_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('project_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('owner_opd_id')->nullable()->constrained('opd')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->nullOnDelete();
            $table->smallInteger('fiscal_year');
            $table->string('status', 40)->default('draft');
            $table->decimal('budget_amount', 20, 2)->nullable();
            $table->string('funding_source', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('needs_review')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('legacy_data_spatial_id')->nullable()->constrained('data_spatial')->nullOnDelete();
            $table->timestamps();
        });

        // Backfill dari data_spatial (masih compatibility source, belum di-retire).
        // Nama proyek pakai fallback berjenjang karena 32% baris tidak punya PAKET.
        // owner_opd_id TIDAK ditebak — hanya diisi bila sudah eksplisit ada di
        // opd_pengelola_id; sisanya NULL + needs_review=true.
        //
        // budget_amount HANYA diparse bila ANGGARAN cocok pola satu angka rupiah
        // yang bersih ("Rp. 733.500.000,00" atau "Rp 733500000"). Beberapa baris
        // ternyata berisi DUA angka digabung dalam satu teks (mis. "Jembatan Rp.
        // 129.600.000.000, Jalan Rp. 264.787.000.000" — rincian per jenis pekerjaan
        // dalam satu proyek gabungan) — kalau dipaksa dibersihkan begitu saja, dua
        // angka itu akan nempel jadi satu angka raksasa yang salah total. Baris
        // seperti ini dibiarkan budget_amount NULL + needs_review=true, bukan ditebak.
        DB::statement("
            INSERT INTO development_projects
                (public_id, project_code, name, owner_opd_id, fiscal_year, status,
                 budget_amount, needs_review, legacy_data_spatial_id, created_at, updated_at)
            SELECT
                gen_random_uuid(),
                'LEGACY-' || ds.id,
                COALESCE(
                    NULLIF(ds.dbf_attributes->>'PAKET', ''),
                    NULLIF(ds.deskripsi, 'Data tanpa nama'),
                    'Proyek Tanpa Nama #' || ds.id
                ),
                ds.opd_pengelola_id,
                COALESCE(ds.tahun, EXTRACT(YEAR FROM now())::int),
                'berjalan',
                CASE
                    WHEN ds.dbf_attributes->>'ANGGARAN' ~ '^\\s*Rp\\.?\\s*[0-9]{1,3}(\\.[0-9]{3})*(,[0-9]+)?\\s*\$'
                    THEN replace(
                        replace(regexp_replace(trim(ds.dbf_attributes->>'ANGGARAN'), '^Rp\\.?\\s*', ''), '.', ''),
                        ',', '.'
                    )::numeric
                    ELSE NULL
                END,
                ds.opd_pengelola_id IS NULL
                    OR (ds.dbf_attributes->>'ANGGARAN' IS NOT NULL
                        AND ds.dbf_attributes->>'ANGGARAN' !~ '^\\s*Rp\\.?\\s*[0-9]{1,3}(\\.[0-9]{3})*(,[0-9]+)?\\s*\$'),
                ds.id,
                now(),
                now()
            FROM data_spatial ds
            WHERE ds.data_type = 'proyek_strategis'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('development_projects');
    }
};
