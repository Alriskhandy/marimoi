# MARIMOI
**Manajemen Akselerasi Infrastruktur untuk Monitoring dan Integrasi Wilayah**

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-1.0.0-green.svg)](https://github.com/yourusername/marimoi/releases)
[![Platform](https://img.shields.io/badge/platform-Web%20%7C%20Mobile-lightgrey.svg)](https://github.com/yourusername/marimoi)

## Deskripsi Proyek

MARIMOI adalah sistem informasi digital terpadu berbasis web dan mobile yang dikembangkan untuk mendukung perencanaan, pelaksanaan, pemantauan, dan evaluasi pembangunan infrastruktur daerah secara lebih efektif, partisipatif, dan terintegrasi. Sistem ini dirancang khusus untuk memperkuat sinergi lintas sektor dan wilayah dalam mendukung pembangunan wilayah Provinsi Maluku Utara.

## Fitur Utama

### 🏗️ **Manajemen Infrastruktur**
- Perencanaan proyek infrastruktur daerah
- Monitoring progress pembangunan real-time
- Evaluasi dan assessment kualitas infrastruktur
- Database terpusat aset infrastruktur

### 📊 **Monitoring dan Evaluasi**
- Dashboard analytics untuk tracking KPI
- Laporan progress otomatis
- Visualisasi data geografis (GIS)
- Sistem peringatan dini (early warning system)

### 🤝 **Integrasi Multi-Sektor**
- Koordinasi antar dinas/instansi
- Sinergi lintas wilayah kabupaten/kota
- Kolaborasi dengan pihak swasta
- Partisipasi masyarakat dalam perencanaan

### 📱 **Multi-Platform**
- Web application untuk admin dan stakeholder
- Mobile app untuk monitoring lapangan
- Progressive Web App (PWA) support
- Responsive design untuk semua perangkat

## Teknologi yang Digunakan

### Frontend
- **Web**: React.js, TypeScript, Tailwind CSS
- **Mobile**: React Native / Flutter
- **Maps**: Leaflet.js / Google Maps API
- **Charts**: Chart.js / D3.js

### Backend
- **Framework**: Laravel 10.x
- **Database**: PostgreSQL dengan PostGIS
- **Authentication**: Laravel Sanctum + Breeze
- **API**: RESTful API dengan Laravel API Resources

### Infrastructure
- **Cloud**: AWS / Google Cloud Platform
- **Container**: Docker + Kubernetes
- **CI/CD**: GitHub Actions / GitLab CI
- **Monitoring**: Prometheus + Grafana

## Instalasi

### Prasyarat
- PHP >= 8.1
- Composer
- PostgreSQL >= 12.0
- Node.js >= 16.0.0 (untuk frontend assets)
- Docker (opsional)
- Git

### Langkah Instalasi

1. **Clone repository**
   ```bash
   git clone https://github.com/yourusername/marimoi.git
   cd marimoi
   ```

2. **Install dependencies**
   ```bash
   # Backend Laravel
   composer install
   
   # Frontend dependencies
   npm install
   ```

3. **Setup database**
   ```bash
   # Buat database PostgreSQL
   createdb marimoi_db
   
   # Copy environment file
   cp .env.example .env
   
   # Generate application key
   php artisan key:generate
   
   # Jalankan migrasi dan seeder
   php artisan migrate --seed
   ```

4. **Setup frontend assets**
   ```bash
   # Compile assets untuk development
   npm run dev
   
   # Atau untuk production
   npm run build
   ```

5. **Jalankan aplikasi**
   ```bash
   # Development server
   php artisan serve
   
   # Queue worker (untuk background jobs)
   php artisan queue:work
   
   # Schedule runner (untuk cron jobs)
   php artisan schedule:run
   ```

### Docker Setup (Alternative)

```bash
# Build dan jalankan dengan Docker Compose
docker-compose up -d

# Install dependencies di dalam container
docker-compose exec app composer install

# Jalankan migrasi database
docker-compose exec app php artisan migrate --seed

# Compile frontend assets
docker-compose exec app npm run dev
```

## Penggunaan

### Dashboard Admin
1. Login dengan akun administrator
2. Akses dashboard utama di `/dashboard`
3. Kelola proyek infrastruktur melalui menu "Proyek"
4. Monitor progress di menu "Monitoring"

### Mobile App
1. Download aplikasi MARIMOI dari Play Store/App Store
2. Login dengan akun yang telah terdaftar
3. Gunakan fitur GPS untuk tracking lokasi proyek
4. Upload foto dan laporan progress langsung dari lapangan

### API Documentation
API documentation tersedia di `/api/documentation` setelah aplikasi berjalan (menggunakan Laravel Swagger).

## Struktur Proyek

```
marimoi/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/
│   ├── Services/
│   └── Providers/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── views/
│   ├── js/
│   └── css/
├── routes/
│   ├── web.php
│   ├── api.php
│   └── console.php
├── public/
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
├── config/
└── docs/
    ├── api/
    ├── user-guide/
    └── deployment/
```

## Kontribusi

Kami menyambut kontribusi dari semua pihak! Silakan baca [CONTRIBUTING.md](CONTRIBUTING.md) untuk panduan berkontribusi.

### Cara Berkontribusi
1. Fork repository ini
2. Buat branch fitur (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## Testing

```bash
# Jalankan unit tests
php artisan test

# Jalankan specific test
php artisan test --filter=ProjectTest

# Jalankan tests dengan coverage
php artisan test --coverage

# Jalankan feature tests
php artisan test tests/Feature

# Jalankan unit tests
php artisan test tests/Unit
```

## Deployment

### Production Deployment
1. Setup server dengan PHP 8.1+, Nginx, PostgreSQL
2. Clone repository dan install dependencies
3. Setup environment variables production
4. Compile frontend assets untuk production
5. Setup queue worker dengan Supervisor
6. Konfigurasi cron jobs untuk Laravel Scheduler
7. Setup SSL certificate dan domain
8. Deploy menggunakan CI/CD pipeline (GitHub Actions/GitLab CI)

Panduan lengkap deployment tersedia di [docs/deployment/](docs/deployment/).

## Roadmap

### Phase 1 (Q1 2025) ✅
- [x] Sistem autentikasi dan otorisasi
- [x] Dashboard monitoring dasar
- [x] CRUD proyek infrastruktur
- [x] Integrasi maps dan GIS

### Phase 2 (Q2 2025) 🔄
- [ ] Mobile application
- [ ] Advanced analytics dan reporting
- [ ] Sistem notifikasi real-time
- [ ] API publik untuk integrasi

### Phase 3 (Q3 2025) 📋
- [ ] Machine learning untuk prediksi
- [ ] Chatbot untuk customer service
- [ ] Advanced workflow management
- [ ] Integration dengan sistem legacy

## Dukungan

### Dokumentasi
- [User Guide](docs/user-guide/)
- [API Documentation](docs/api/)
- [Developer Guide](docs/developer-guide/)

### Kontak
- **Email**: support@marimoi.id
- **Website**: https://marimoi.id
- **GitHub Issues**: [Issues](https://github.com/yourusername/marimoi/issues)

### Tim Pengembang
- **Project Manager**: [Nama PM]
- **Lead Developer**: [Nama Lead Dev]
- **UI/UX Designer**: [Nama Designer]
- **DevOps Engineer**: [Nama DevOps]

## Lisensi

Proyek ini dilisensikan di bawah MIT License - lihat file [LICENSE](LICENSE) untuk detail lengkap.

## Penghargaan

Terima kasih kepada semua kontributor dan stakeholder yang telah mendukung pengembangan MARIMOI:

- Pemerintah Provinsi Maluku Utara
- Dinas Pekerjaan Umum dan Penataan Ruang
- Badan Perencanaan Pembangunan Daerah
- Seluruh masyarakat Maluku Utara

---

**MARIMOI** - Membangun Maluku Utara yang Lebih Terintegrasi dan Berkelanjutan

*Dikembangkan dengan ❤️ untuk kemajuan infrastruktur Maluku Utara*