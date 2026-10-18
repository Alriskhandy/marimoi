---
paths:
  - 'app/**'
---

# App

## Modul "Jenis Peta" sudah dihapus — jangan dibangkitkan
Tabel `map_types` + `map_type_dynamic_attributes`, MapType/MapTypeDynamicAttribute, MapTypeController, MetadataDefinitionController, route `map-types.*` + `metadata-definitions.search`, permission `map-types.manage`, dan command `marimoi:granularize-map-types` dihapus 2026-10-10 (migration `drop_map_types_tables`). Pemicunya: kolom `layers.map_type_id` sudah dilepas 2026-10-06, jadi tidak ada Layer yang bisa dikaitkan ke sebuah Jenis.

Konsekuensi yang harus diingat: "Metadata Dinamis" SELALU kosong. Empat method ini sengaja dipertahankan sebagai titik sambung tunggal yang `return collect()`/`return []` — jangan dihapus, jangan dianggap bug:
- `LayerImportPipeline::activeDynamicAttributesFor()`
- `SpatialLayerFeatureController::activeDynamicAttributesFor()`
- `LayerStyleController::classificationFieldsFor()` (akibatnya style categorized/graduated tak punya kandidat field)
- `SpatialMapController::labeledMetadataDinamis()`

Yang TETAP hidup: katalog `metadata_definitions` (target `layer_attribute_mappings.attribute_definition_id` + command `marimoi:migrate-metadata-definitions`), dan kolom inert `spatial_layers_legacy_v2.map_type_id` (FK-nya dilepas, nilai historis dibiarkan; tak ada kode yang membacanya).

## Halaman "Dashboard Pembangunan" sudah dihapus — data progres tetap ada
Route `dashboard.pembangunan`, `PembangunanDashboardController`, view `backend/pages/dashboard-pembangunan`, dan permission `project-progress.view` dihapus 2026-10-10 (migration `drop_project_progress_view_permission`, plus prefix `project-progress.` di `PermissionSeeder::RETIRED_PREFIXES`). Sidebar "Dashboard" kembali jadi satu link tunggal — grup collapse Ringkasan/Pembangunan hilang bersamanya.

Yang TETAP hidup dan jangan ikut dihapus: tabel `project_progress_reports`, model `ProjectProgressReport` + factory-nya, `ProjectProgressReportRevision`, dan `DataSpatial::proyekStrategis()`. Semuanya masih dipakai `ExecutiveDashboardController` (endpoint `dashboard/api/eksekutif/*`) dan `DevelopmentProject`, teruji lewat ExecutiveDashboardTest + DevelopmentProjectTest.

## Peta Tematik publik membaca V3 lewat PublicMapCatalog
Semua endpoint publik peta (`/geojson` metadata & feature, `/geojson/filter-options`, `/geojson/filter-categories`, `MapDataVersion`) membaca skema V3 (layers/spatial_features/categories_v3/category_nodes), bukan tabel kompatibilitas v1 `categories`/`data_spatial`. Hanya Layer `status = published` yang tampil — draft tidak boleh bocor ke publik.
Satu mapset = satu Layer. Frontend (map.js/map-catalog.js) mengenali mapset dari NAMA, jadi nama tampilan unik dibuat di `App\Support\PublicMapCatalog` (nama kembar diberi konteks node/kategori) dan WAJIB dipakai bersama untuk metadata, properti `kategori` tiap feature, parameter `kategori[]`, dan hasil filter — jangan menghitung nama di tempat lain.
Pohon untuk frontend dibatasi 3 level: Kategori › jalur node (digabung "A › B") › Layer.

## Template Dokumen: logo lewat rute aplikasi, data ke peta lewat view composer
Kop/footer hasil Unduh Peta & cetak Analisis diambil dari tabel `document_templates` (DocumentTemplate::publicList(), hanya is_active, bawaan dulu) dan dikirim ke `frontend.pages.peta` lewat View::composer di AppServiceProvider — jangan diteruskan per-controller (peta dirender dari 3 rute).
Logo WAJIB disajikan lewat rute `document-templates.logo` (Storage::disk('public')->response), bukan asset('storage/...'): canvas Unduh Peta butuh gambar satu origin (kalau tidak, canvas "tainted" dan unduhan gagal) dan tidak boleh bergantung pada storage:link. Hanya satu template is_default; makeDefault() yang menjaganya.

## Feedback masyarakat satu pintu: project_feedbacks untuk semua data spasial
"Feedback Pemetaan" (SpatialFeedbackController, model SpatialFeedback, route/permission `spatial-feedbacks.*`, tabel `spatial_feedbacks`) dihapus 2026-10-14 (migration `drop_spatial_feedbacks_feature`, prefix ada di PermissionSeeder::RETIRED_PREFIXES) — jangan dibangkitkan.
Feedback berlaku untuk SEMUA data spasial, tanpa konsep type (tematik/psd/psn/pokir/musrenbang): ProjectFeedbackController tidak memfilter data_type, menu sidebar tanpa `?type`. Halaman publik `detail.interaktif` memakai view `frontend.pages.detail` yang sama (form feedback selalu tampil).
Cakupan akses lewat ProjectFeedbackController::accessibleFeedbacks() (super-admin/admin-bappeda semua; role lain: opd_id miliknya ATAU data spasial miliknya) — pakai untuk list, statistik, show/update/respond/destroy. Dropdown data spasial di form admin WAJIB via AJAX (`project-feedbacks.data-spatial`), jumlahnya ±12 ribu. Lampiran gambar di disk public folder `aspirasi_lampiran`.

## Email aspirasi & feedback lewat AspirasiMailer
Semua email Aspirasi/Feedback dikirim via App\Support\AspirasiMailer::queue() (per penerima, clone mailable, error hanya di-log — submit warga tidak boleh 500 karena mailer). Penerima admin = AspirasiMailer::adminRecipients(): config mail.admin_addresses (env MAIL_ADMIN_ADDRESS, koma), fallback super-admin aktif — jangan pakai role_id=1 atau mail.from.address sebagai penerima.
AspirasiMail/TanggapanMail: ShouldQueue + afterCommit + tries/backoff, isi disusun di method message() dan dirender view bersama emails.notification (+ emails.notification-text). TanggapanMail::CONTEXT_ASPIRASI untuk email status aspirasi. Nomor tiket feedback = ProjectFeedback::ticketCode().
Email perubahan status hanya dikirim bila wasChanged(status/tanggapan). aspirasi.admin_id adalah FK users — jangan isi ID OPD (OPD tujuan lewat kategori_aspirasi.opd_id). POST publik pakai rate limiter `public-submissions` (5/menit/IP).

## hCaptcha dilewati hanya di APP_ENV=local
Pakai ValidHCaptcha::isRequired() (false hanya saat APP_ENV=local) untuk menentukan apakah rule `h-captcha-response` dipasang DAN apakah script/widget hCaptcha dirender — keduanya harus sinkron (contoh: aspirasiStore + view frontend.pages.aspirasi). Env testing/production tetap wajib captcha. Test yang mengganti `$this->app['env']` harus withoutMiddleware(ValidateCsrfToken::class), karena CSRF hanya otomatis dilewati di env testing.

## Lacak tiket aspirasi & feedback lewat TicketLookup
Form #lacak di halaman Aspirasi melacak DUA jenis tiket: MARIMOI-ASP-* (Aspirasi.nomor_tiket) dan MARIMOI-FDB-* (ProjectFeedback::ticketCode(), tidak disimpan di DB — di-parse dari id lalu dicocokkan ulang termasuk tanggal). Semua pencarian/ringkasan lewat App\Support\TicketLookup (find/summary, kontak email ATAU WhatsApp wajib cocok); session menyimpan `lacakTiket` = {type,id}. Status feedback dipetakan ke tahapan aspirasi (ditinjau/ditindaklanjuti → diproses).
Email konfirmasi ke warga wajib memuat nomor tiket + TicketLookup::trackingSteps() + tombol TicketLookup::trackingUrl($tiket) (?tiket= mengisi form & membuka tab Lacak). Email notifikasi admin/OPD tidak memuat panduan lacak.

## Log Email: khusus super-admin via role, dicatat otomatis oleh listener
Menu Sistem › Log Email (route `mail-logs.index`, MailLogController) dikunci middleware `role:super-admin` + sidebar `$isSuperAdmin` — sengaja BUKAN permission, agar tidak bisa didelegasikan lewat Manajemen Role. Jangan ganti ke `permission:`.
Tabel mail_logs diisi otomatis oleh App\Listeners\RecordMailLog (auto-discovered): MessageSent → sent, JobFailed untuk SendQueuedMailable → failed (setelah retry habis). Hanya metadata (penerima, subjek, jenis, error) — isi email tidak disimpan. Retensi MailLog::RETENTION_DAYS (90) via MassPrunable + `model:prune` terjadwal harian di routes/console.php. Test yang memakai Mail::fake() tidak menghasilkan log.
