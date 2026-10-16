<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retire fitur Survey Kepuasan: tabel `surveys` beserta model, controller,
     * form request, view, seeder dan factory-nya. Route publik `/survey` sudah
     * lama di-comment dan download publikasi hanya menulis ke
     * `publication_downloads`, jadi tabel ini tidak pernah terisi.
     */
    public function up(): void
    {
        Schema::dropIfExists('surveys');
    }

    /**
     * Memulihkan struktur (kolom, CHECK, FK) persis seperti sebelum di-drop.
     * Isi tabel TIDAK bisa dipulihkan — rollback memulihkan struktur, bukan data.
     */
    public function down(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE surveys (
                id bigserial PRIMARY KEY,
                publication_id bigint NULL,
                publication_download_id bigint NULL,
                name varchar(255) NOT NULL,
                email varchar(255) NOT NULL,
                phone varchar(255) NULL,
                organization varchar(255) NULL,
                position varchar(255) NULL,
                survey_type varchar(255) NOT NULL DEFAULT 'general',
                rating integer NOT NULL,
                feedback text NULL,
                suggestions text NULL,
                additional_data json NULL,
                ip_address varchar(255) NOT NULL,
                user_agent varchar(255) NULL,
                created_at timestamp NULL,
                updated_at timestamp NULL,
                CONSTRAINT surveys_survey_type_check CHECK (survey_type IN ('download', 'general')),
                CONSTRAINT surveys_publication_download_id_foreign FOREIGN KEY (publication_download_id) REFERENCES publication_downloads(id) ON DELETE CASCADE,
                CONSTRAINT surveys_publication_id_foreign FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE
            )
        SQL);
    }
};
