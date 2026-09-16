# Plan Authentication, User, dan Multi-Role

## Tujuan

Menyediakan login publik melalui Google, provisioning admin yang terkontrol, serta authorization berbasis role dan scope OPD.

## Role

| Role | Provisioning | Scope |
| --- | --- | --- |
| `admin-sistem` | internal | seluruh sistem dan konfigurasi |
| `admin-bappeda` | Admin Sistem | data lintas OPD sesuai kewenangan Bappeda |
| `admin-opd` | Admin Sistem | data, proyek, dan laporan OPD terkait |
| `publik` | otomatis setelah Google login | fitur publik yang membutuhkan autentikasi |

Role publik tidak boleh naik privilege melalui input registrasi. Perubahan role admin hanya melalui workflow Admin Sistem dan dicatat di activity log.

## Data dan Relasi

- `users` memiliki `role_id`, `opd_id`, status aktif, dan identitas provider.
- Tambahkan tabel `social_accounts` bila satu user dapat memiliki beberapa provider.
- Tambahkan histori `user_role_assignments` bila perubahan role perlu diaudit.
- Scope OPD diterapkan melalui `owner_opd_id` pada layer, map, proyek, dan laporan.
- Role tidak menggantikan Policy; setiap resource tetap memeriksa ownership dan permission.

## Alur Login

1. User memilih login Google.
2. Provider callback diverifikasi melalui state/CSRF dan email/provider identity.
3. User baru dibuat dengan role `publik` dan status aktif sesuai kebijakan.
4. User admin yang sudah diprovisioning dikenali melalui identity yang terdaftar.
5. Login berhasil/gagal dicatat pada authentication log.
6. Dashboard memakai middleware autentikasi dan policy scope.

## Kriteria Selesai

- user publik tidak dapat mengakses route admin;
- Admin OPD tidak dapat mengubah data OPD lain;
- Admin Sistem dapat membuat, menonaktifkan, dan mengganti role admin;
- logout, revoked account, callback gagal, dan duplicate identity diuji;
- semua role change memiliki actor, waktu, nilai lama, dan nilai baru.
