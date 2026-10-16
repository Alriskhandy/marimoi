# Plan Security dan Audit

## Tujuan

Meningkatkan keamanan autentikasi, data publik, upload spasial, dan keterlacakan tindakan user.

## Audit Log

Pisahkan minimal:

- `authentication_logs`: login/logout, provider, success/failure, IP, user-agent, session identifier, dan reason;
- `activity_logs`: actor, action, auditable type/id, old values, new values, request id, IP, dan user-agent;
- `map_share_accesses`: akses share dengan IP hash, waktu, referer, dan response status.

Data sensitif harus diminimalkan, diberi retensi, dan tidak ditulis ke log aplikasi secara sembarangan.

## Kontrol Akses

- gunakan middleware untuk authentication dan role gate;
- gunakan Policy untuk resource layer, map, proyek, feedback, dan publication;
- gunakan Form Request untuk validasi dan authorization request;
- route share hanya read-only dan tidak memberi hak edit;
- publik hanya membaca publication aktif dan layer yang diizinkan;
- gunakan named route, rate limit, expiry, dan revoke untuk share token;
- token disimpan sebagai hash bila plaintext tidak perlu disimpan.

## Upload dan Geometri

- whitelist ekstensi, MIME, ukuran, dan struktur archive;
- cegah path traversal dan file executable;
- simpan file di storage terkelola, bukan sebagai blob database;
- proses upload di job terisolasi setelah validasi dasar;
- validasi SRID, `ST_IsValid`, empty geometry, dan jumlah feature;
- simpan checksum agar import dapat dibuat idempotent.

## Kriteria Selesai

- authorization test untuk setiap role dan resource;
- audit log tidak dapat diubah melalui endpoint biasa;
- secret tidak muncul pada log/response;
- rate limit dan CSRF/State OAuth diuji;
- prosedur retensi dan penghapusan data pribadi ditetapkan.
