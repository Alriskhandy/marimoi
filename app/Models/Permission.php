<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * Katalog permission per modul: kunci modul => [label, aksi => label].
     * Nama permission berbentuk "{modul}.{aksi}" (contoh: aspirasi.view).
     *
     * @var array<string, array{label: string, actions: array<string, string>}>
     */
    public const CATALOG = [
        'dashboard' => [
            'label' => 'Dashboard',
            'actions' => ['view' => 'Lihat dashboard'],
        ],
        // 'data-spatial' dihapus — Fase I/D13 (plan mellow-weaving-eclipse):
        // modul "Data Spasial" lama di-retire, digantikan 'spatial-layers'.
        'categories' => [
            'label' => 'Kategori Peta',
            'actions' => [
                'view' => 'Lihat kategori',
                'create' => 'Tambah kategori',
                'edit' => 'Ubah kategori',
                'delete' => 'Hapus kategori',
            ],
        ],
        // 'map-types' dihapus 2026-10-10 — modul "Jenis Peta" di-retire utuh
        // (controller/model/view/tabel), lihat migration drop_map_types_tables.
        // Baris permission `map-types.manage` yang sudah ada di DB dibersihkan
        // migration itu juga, termasuk pivot role_has_permissions-nya.
        'spatial-layers' => [
            'label' => 'Daftar Layer & Data',
            'actions' => [
                'view' => 'Lihat Layer & Data Spasial',
                'create' => 'Tambah Layer & Data Spasial',
                'edit' => 'Ubah Layer & Data Spasial',
                'delete' => 'Hapus Layer & Data Spasial',
                'publish' => 'Ubah status Layer (draft/published/archived)',
            ],
        ],
        'spatial-feedbacks' => [
            'label' => 'Feedback Pemetaan',
            'actions' => [
                'view' => 'Lihat feedback Layer/Data Spasial',
                'respond' => 'Tanggapi feedback',
                'delete' => 'Hapus feedback',
            ],
        ],
        'project-feedbacks' => [
            'label' => 'Feedback Peta',
            'actions' => [
                'view' => 'Lihat feedback',
                'respond' => 'Tanggapi / ubah feedback',
                'delete' => 'Hapus feedback',
            ],
        ],
        // 'project-progress' dihapus 2026-10-10 — halaman "Dashboard
        // Pembangunan" (dashboard.pembangunan) di-retire; permission ini satu-
        // satunya penjaga route + submenu sidebar-nya. Tabel
        // `project_progress_reports` sendiri TETAP ADA (dipakai dashboard
        // eksekutif), cuma halaman ini yang hilang.
        'aspirasi' => [
            'label' => 'Aspirasi',
            'actions' => [
                'view' => 'Lihat aspirasi',
                'create' => 'Tambah aspirasi',
                'edit' => 'Ubah aspirasi & status',
                'delete' => 'Hapus aspirasi',
                'export' => 'Ekspor aspirasi',
            ],
        ],
        'kategori-aspirasi' => [
            'label' => 'Kategori Aspirasi',
            'actions' => [
                'view' => 'Lihat kategori aspirasi',
                'create' => 'Tambah kategori aspirasi',
                'edit' => 'Ubah kategori aspirasi',
                'delete' => 'Hapus kategori aspirasi',
            ],
        ],
        'opd' => [
            'label' => 'Manajemen OPD',
            'actions' => [
                'view' => 'Lihat OPD',
                'create' => 'Tambah OPD',
                'edit' => 'Ubah OPD',
                'delete' => 'Hapus OPD',
            ],
        ],
        'users' => [
            'label' => 'Manajemen Pengguna',
            'actions' => [
                'view' => 'Lihat pengguna',
                'create' => 'Tambah pengguna',
                'edit' => 'Ubah pengguna',
                'delete' => 'Hapus pengguna',
            ],
        ],
        'roles' => [
            'label' => 'Manajemen Role & Hak Akses',
            'actions' => [
                'view' => 'Lihat role',
                'create' => 'Tambah role',
                'edit' => 'Ubah role & hak akses',
                'delete' => 'Hapus role',
            ],
        ],
        'publications' => [
            'label' => 'Manajemen Publikasi',
            'actions' => [
                'view' => 'Lihat publikasi & data download',
                'create' => 'Tambah publikasi',
                'edit' => 'Ubah publikasi',
                'delete' => 'Hapus publikasi & data download',
            ],
        ],
        'visitors' => [
            'label' => 'Analisis Pengunjung',
            'actions' => [
                'view' => 'Lihat analisis pengunjung',
                'delete' => 'Hapus data pengunjung',
            ],
        ],
        'logs' => [
            'label' => 'Log Sistem',
            'actions' => [
                'view' => 'Lihat log',
                'manage' => 'Unduh & bersihkan log',
            ],
        ],
    ];

    /**
     * Seluruh nama permission dari katalog.
     *
     * @return array<int, string>
     */
    public static function catalogNames(): array
    {
        $names = [];

        foreach (self::CATALOG as $module => $definition) {
            foreach (array_keys($definition['actions']) as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }
}
