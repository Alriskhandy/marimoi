<?php

return [
    // Tanggal mulai berlakunya kewajiban mengisi sumber_data/opd_pengelola_id/tanggal_data
    // saat input data spasial baru (lihat docs/marimoi v2/03_plan/12-rekomendasi-perbaikan-v2.md
    // Prioritas 1.2 Opsi B). Data yang dibuat sebelum tanggal ini tetap nullable — tidak retroaktif.
    'metadata_wajib_sejak' => env('METADATA_WAJIB_SEJAK', '2026-10-01'),
];
