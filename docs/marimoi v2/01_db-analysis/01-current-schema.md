```mermaid
erDiagram
	roles ||--o{ users : "role_id SET NULL"
	opd ||--o{ users : "opd_id SET NULL"
	users ||--o{ data_spatial : "user_id SET NULL"
	categories ||--o{ categories : "parent_id"
	categories ||--o{ data_spatial : "kategori_id RESTRICT"
	opd ||--o{ kategori_aspirasi : "opd_id SET NULL"
	kategori_aspirasi ||--o{ aspirasi : "kategori_aspirasi_id CASCADE"
	users ||--o{ aspirasi : "admin_id SET NULL"
	data_spatial ||--o{ project_feedbacks : "data_spatial_id SET NULL"
	opd ||--o{ project_feedbacks : "opd_id SET NULL"
	publications ||--o{ publication_downloads : "publication_id CASCADE"
	publications ||--o{ surveys : "publication_id CASCADE"
	publication_downloads ||--o{ surveys : "publication_download_id CASCADE"

	roles {
		bigint id PK
		varchar name UK
		varchar slug UK
		varchar description
		timestamp created_at
		timestamp updated_at
	}

	opd {
		bigint id PK
		varchar name
		varchar singkatan UK
		varchar logo
		varchar telepon
		varchar email
		timestamp created_at
		timestamp updated_at
	}

	users {
		bigint id PK
		varchar name
		varchar email UK
		bigint role_id FK
		bigint opd_id FK
		timestamp email_verified_at
		varchar password
		varchar remember_token
		timestamp created_at
		timestamp updated_at
	}

	cache {
		varchar key PK
		mediumtext value
		integer expiration
	}

	cache_locks {
		varchar key PK
		varchar owner
		integer expiration
	}

	jobs {
		bigint id PK
		varchar queue
		longtext payload
		tinyint attempts
		integer reserved_at
		integer available_at
		integer created_at
	}

	job_batches {
		varchar id PK
		varchar name
		integer total_jobs
		integer pending_jobs
		integer failed_jobs
		longtext failed_job_ids
		mediumtext options
		integer cancelled_at
		integer created_at
		integer finished_at
	}

	failed_jobs {
		bigint id PK
		varchar uuid UK
		text connection
		text queue
		longtext payload
		longtext exception
		timestamp failed_at
	}

	password_reset_tokens {
		varchar email PK
		varchar token
		timestamp created_at
	}

	sessions {
		varchar id PK
		bigint user_id
		varchar ip_address
		text user_agent
		longtext payload
		integer last_activity
	}

	dokumens {
		bigint id PK
		varchar nama
		varchar file
		timestamp created_at
		timestamp updated_at
	}

	categories {
		bigint id PK
		varchar type INDEX
		varchar nama
		varchar warna
		varchar icon
		boolean is_marker
		boolean is_active INDEX
		varchar gambar
		text deskripsi
		bigint parent_id FK
		bigint user_id
		timestamp created_at
		timestamp updated_at
	}

	data_spatial {
		bigint id PK
		bigint user_id FK
		varchar uuid UK
		varchar data_type INDEX
		varchar sub_type
		varchar gambar
		bigint kategori_id FK
		text deskripsi
		jsonb dbf_attributes
		integer tahun
		integer views
		timestamp created_at
		timestamp updated_at
		geometry geom
	}

	kategori_aspirasi {
		bigint id PK
		bigint opd_id FK
		varchar nama_kategori
		text deskripsi
		timestamp created_at
		timestamp updated_at
	}

	aspirasi {
		bigint id PK
		bigint kategori_aspirasi_id FK
		varchar nomor_tiket UK
		varchar nama_pengirim
		varchar email
		varchar phone
		text alamat
		enum jenis_aspirasi
		varchar judul_aspirasi
		text isi_aspirasi
		json lampiran
		decimal latitude
		decimal longitude
		text tanggapan_admin
		enum status
		timestamp tanggal_respon
		bigint admin_id FK
		timestamp created_at
		timestamp updated_at
	}

	visitors {
		bigint id PK
		varchar ip
		text user_agent
		decimal latitude
		decimal longitude
		varchar country
		varchar city
		varchar page_visited
		timestamp created_at
		timestamp updated_at
	}

	project_feedbacks {
		bigint id PK
		bigint data_spatial_id FK
		bigint opd_id FK
		varchar nama_pemberi_aspirasi
		varchar nama_proyek
		varchar kabupaten_kota
		varchar kecamatan
		decimal latitude
		decimal longitude
		varchar laporan_gambar
		text tanggapan
		enum jenis_tanggapan
		enum status
		varchar email
		varchar phone
		text response_admin
		timestamp responded_at
		timestamp created_at
		timestamp updated_at
		geometry geom
	}

	publications {
		bigint id PK
		varchar title
		text description
		varchar file_name
		varchar file_path
		varchar file_type
		bigint file_size
		varchar cover
		varchar category
		integer download_count
		timestamp created_at
		timestamp updated_at
	}

	publication_downloads {
		bigint id PK
		bigint publication_id FK
		varchar name
		varchar email
		varchar phone
		varchar organization
		varchar position
		varchar purpose
		varchar ip_address
		varchar user_agent
		timestamp downloaded_at
		timestamp created_at
		timestamp updated_at
	}

	surveys {
		bigint id PK
		bigint publication_id FK
		bigint publication_download_id FK
		varchar name
		varchar email
		varchar phone
		varchar organization
		varchar position
		enum survey_type
		integer rating
		text feedback
		text suggestions
		json additional_data
		varchar ip_address
		varchar user_agent
		timestamp created_at
		timestamp updated_at
	}

	api_tokens {
		bigint id PK
		varchar name
		varchar token UK
		timestamp last_used_at
		timestamp expires_at
		timestamp created_at
		timestamp updated_at
	}
```

### Catatan

- Diagram menggambarkan keadaan final setelah seluruh migration di repository dijalankan.
- `sessions.user_id` dan `categories.user_id` mengarah secara logis ke `users.id`, tetapi tidak memiliki foreign key constraint.
- `migrations` adalah tabel internal Laravel dan tidak dibuat oleh migration aplikasi.
- Kolom `geom` pada `project_feedbacks` hanya tersedia jika PostGIS aktif saat migration dijalankan. `data_spatial.geom` menggunakan PostGIS.
- Nilai enum: `aspirasi.jenis_aspirasi` (`usulan`, `kritik & saran), `aspirasi.status` (`pending`, `diproses`, `selesai`, `ditolak`), `project_feedbacks.jenis_tanggapan` (`keluhan`, `saran`, `apresiasi`, `pertanyaan`), `project_feedbacks.status` (`pending`, `ditinjau`, `ditindaklanjuti`, `selesai`), dan `surveys.survey_type` (`download`, `general`).
