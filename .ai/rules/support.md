---
paths:
  - app/Support/DashboardMetrics.php
---

# Support

## Dashboard dibedakan per role lewat DashboardMetrics
DashboardController::index() memakai App\Support\DashboardMetrics: role `pimpinan` → profil executive (view backend/pages/dashboard/executive), `admin-opd` → profil opd (semua angka difilter layers.opd_id / kategori_aspirasi.opd_id / development_projects.owner_opd_id; fitur = spatial_features.created_by user), role lain → admin (operational). Super-admin & admin-bappeda bisa beralih ke executive lewat `?tampilan=executive` (DashboardMetrics::EXECUTIVE_SWITCHERS); pimpinan & admin-opd tidak bisa beralih. Role pimpinan + dashboard.view dibuat oleh migration grant_dashboard_view_to_pimpinan_role. View variable `totalLokasi` tetap dipertahankan (DashboardTotalLokasiTest).
