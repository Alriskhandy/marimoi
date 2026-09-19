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
        'data-spatial' => [
            'label' => 'Data Spasial & Peta',
            'actions' => [
                'view' => 'Lihat data & peta',
                'create' => 'Tambah data',
                'edit' => 'Ubah data',
                'delete' => 'Hapus data',
            ],
        ],
        'categories' => [
            'label' => 'Kategori Peta',
            'actions' => [
                'view' => 'Lihat kategori',
                'create' => 'Tambah kategori',
                'edit' => 'Ubah kategori',
                'delete' => 'Hapus kategori',
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
        'dokumen' => [
            'label' => 'Upload Dokumen',
            'actions' => [
                'view' => 'Lihat dokumen',
                'create' => 'Unggah dokumen',
                'edit' => 'Ubah dokumen',
                'delete' => 'Hapus dokumen',
            ],
        ],
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
