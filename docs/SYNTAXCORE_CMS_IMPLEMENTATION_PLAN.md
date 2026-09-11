# SYNTAXCORE CMS IMPLEMENTATION & TASK ROADMAP
Dokumen Spesifikasi Teknis, Arsitektur Data, dan Lembar Kerja Tugas Modul CMS Website Publik

---

## 1. Ringkasan & Konsep Arsitektur

Modul **CMS (Content Management System)** pada SyntaxCore dirancang untuk membangun dan menyajikan website publik (di luar area admin) yang dinamis, berorientasi SEO, dan fleksibel.

### Prinsip Utama Konsep:
1. **Decoupling Konten dan Wadah Representasi**:
   - **News (Berita / Artikel)**: Entitas konten murni yang memiliki judul, konten, thumbnail, kategori, tag, dan riwayat komentar.
   - **Pages (Halaman)**: Wadah publik yang menentukan struktur tata letak (layout), tipe halaman (*standard*, *news feed/index*, atau *news reader/single*), serta konfigurasi representasi komentar.
2. **Kustomisasi Komentar Berbasis Halaman (*Page-Controlled Comment Presentation*)**:
   - Komentar secara entitas data **hanya terikat pada Berita** (`news_id`), bukan pada Page.
   - Namun, **halaman (*Page*)** yang menjadi penampil detail berita memiliki wewenang mengonfigurasi bagaimana komentar ditampilkan (apakah komentar diaktifkan, gaya layout *cards / threaded / minimal*, izin komentar pengunjung tanpa login, paginasi komentar, dsb.).
3. **Pemisahan Publik vs Desktop Admin Windows**:
   - Area Publik disajikan via controller `Web` melalui rute `routes/web.php`.
   - Area Pengelolaan disajikan dalam bentuk jendela aplikasi desktop (*Desktop Window Environment*) di `#wd-workspace`.

---

## 2. Diagram Relasi Entitas Data (ERD)

```
┌─────────────────┐       ┌─────────────────┐
│  public_menus   │       │      pages      │
├─────────────────┤       ├─────────────────┤
│ id              │       │ id              │
│ parent_id       │       │ title           │
│ title           │       │ slug (UNIQUE)   │
│ link_type       │       │ page_type       │ (standard, news_index, news_single)
│ link_target     │       │ comment_settings│ (JSON: layout, allow_guest, per_page)
│ url             │       │ meta_title      │
│ sort_order      │       │ meta_description│
│ is_active       │       │ status          │ (published, draft)
└─────────────────┘       └─────────────────┘

┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│   categories    │       │      news       │       │    comments     │
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ id              │       │ id              │       │ id              │
│ parent_id       │◄──────┤ category_id     │◄──────┤ news_id         │
│ name            │       │ user_id (author)│       │ parent_id       │
│ slug (UNIQUE)   │       │ title           │       │ user_id (opt)   │
│ color           │       │ slug (UNIQUE)   │       │ author_name     │
│ description     │       │ summary         │       │ author_email    │
└─────────────────┘       │ content         │       │ content         │
                          │ featured_image  │       │ status          │ (pending, approved,
                          │ allow_comments  │       │                 │  spam, rejected)
┌─────────────────┐       │ views_count     │       │ ip_address      │
│      tags       │       │ status          │       │ user_agent      │
├─────────────────┤       │ published_at    │       └─────────────────┘
│ id              │       └────────┬────────┘
│ name            │                │
│ slug (UNIQUE)   │                ▼
└────────┬────────┘       ┌─────────────────┐
         │                │    news_tag     │
         └───────────────►│ (news_id,tag_id)│
                          └─────────────────┘
```

---

## 3. Spesifikasi Skema Database

### 3.1 Tabel `categories`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `parent_id` INT NULL (Foreign key ke `categories.id` ON DELETE SET NULL)
- `name` VARCHAR(100) NOT NULL
- `slug` VARCHAR(100) NOT NULL UNIQUE
- `color` VARCHAR(30) NULL DEFAULT '#0d6efd' (Warna badge kategori)
- `description` VARCHAR(255) NULL
- `created_at`, `updated_at` DATETIME NULL

### 3.2 Tabel `tags`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `name` VARCHAR(50) NOT NULL
- `slug` VARCHAR(50) NOT NULL UNIQUE
- `created_at`, `updated_at` DATETIME NULL

### 3.3 Tabel `news`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `category_id` INT NULL (Foreign key ke `categories.id` ON DELETE SET NULL)
- `user_id` INT NULL (Penulis berita, foreign key ke `users.id` ON DELETE SET NULL)
- `title` VARCHAR(255) NOT NULL
- `slug` VARCHAR(255) NOT NULL UNIQUE
- `summary` TEXT NULL (Kutipan ringkas / excerpt)
- `content` LONGTEXT NOT NULL (Isi berita lengkap)
- `featured_image` VARCHAR(255) NULL (Path gambar thumbnail)
- `allow_comments` TINYINT(1) NOT NULL DEFAULT 1 (Toggle komentar spesifik per berita)
- `views_count` INT NOT NULL DEFAULT 0
- `status` VARCHAR(20) NOT NULL DEFAULT 'published' ('published', 'draft', 'archived')
- `published_at` DATETIME NULL
- `created_at`, `updated_at` DATETIME NULL

### 3.4 Tabel `news_tag` (Pivot)
- `news_id` INT NOT NULL (Foreign key ke `news.id` ON DELETE CASCADE)
- `tag_id` INT NOT NULL (Foreign key ke `tags.id` ON DELETE CASCADE)
- PRIMARY KEY (`news_id`, `tag_id`)

### 3.5 Tabel `pages`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `title` VARCHAR(200) NOT NULL
- `slug` VARCHAR(200) NOT NULL UNIQUE
- `content` LONGTEXT NULL (Konten teks/HTML untuk page standar)
- `page_type` VARCHAR(30) NOT NULL DEFAULT 'standard' ('standard', 'news_index', 'news_single')
- `comment_settings` JSON NULL (Konfigurasi tampilan komentar)
  - `enable_comments`: bool
  - `layout_style`: string ('cards', 'threaded', 'minimal')
  - `allow_guest`: bool
  - `per_page`: int
  - `auto_approve`: bool
- `meta_title` VARCHAR(255) NULL
- `meta_description` TEXT NULL
- `status` VARCHAR(20) NOT NULL DEFAULT 'published' ('published', 'draft')
- `sort_order` INT NOT NULL DEFAULT 0
- `created_at`, `updated_at` DATETIME NULL

### 3.6 Tabel `comments`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `news_id` INT NOT NULL (Foreign key ke `news.id` ON DELETE CASCADE)
- `parent_id` INT NULL (Foreign key ke `comments.id` ON DELETE CASCADE, untuk balasan)
- `user_id` INT NULL (Foreign key ke `users.id` ON DELETE SET NULL jika user login)
- `author_name` VARCHAR(100) NOT NULL
- `author_email` VARCHAR(150) NOT NULL
- `content` TEXT NOT NULL
- `status` VARCHAR(20) NOT NULL DEFAULT 'pending' ('pending', 'approved', 'spam', 'rejected')
- `ip_address` VARCHAR(45) NULL
- `user_agent` VARCHAR(255) NULL
- `created_at`, `updated_at` DATETIME NULL

### 3.7 Tabel `public_menus`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `parent_id` INT NULL (Foreign key ke `public_menus.id` ON DELETE CASCADE)
- `title` VARCHAR(100) NOT NULL
- `link_type` VARCHAR(30) NOT NULL DEFAULT 'page' ('page', 'category', 'custom')
- `link_target` VARCHAR(100) NULL (Slug page atau ID kategori yang dituju)
- `url` VARCHAR(255) NULL (Untuk tautan kustom/eksternal)
- `sort_order` INT NOT NULL DEFAULT 0
- `is_active` TINYINT(1) NOT NULL DEFAULT 1
- `created_at`, `updated_at` DATETIME NULL

---

## 4. Lembar Kerja Tugas & Checklist Eksekusi (*Task Checklist*)

Dokumen ini wajib diperbarui statusnya (`[ ]` -> `[x]`) setiap kali suatu sub-tugas atau fase selesai dieksekusi.

### Fase 1: Skema Database, ORM Models, & Seeder CMS
- [x] **Task 1.1**: Tambahkan DDL tabel-tabel CMS (`categories`, `tags`, `news`, `news_tag`, `pages`, `comments`, `public_menus`) ke `database/schema.sql`.
- [x] **Task 1.2**: Eksekusi pembuatan tabel di database MySQL container (`docker compose exec`).
- [x] **Task 1.3**: Buat class model ORM:
  - [x] `App\Models\Category`
  - [x] `App\Models\Tag`
  - [x] `App\Models\News`
  - [x] `App\Models\Page`
  - [x] `App\Models\Comment`
  - [x] `App\Models\PublicMenu`
- [x] **Task 1.4**: Buat data seeder awal di `database/seed.php`:
  - Kategori default (*Umum*, *Teknologi*, *Pengumuman*).
  - Contoh tags (*SyntaxCore*, *Update*, *Tutorial*).
  - Contoh artikel berita lengkap dengan excerpt & tags.
  - Halaman default (*Beranda*, *Tentang Kami*, *Berita*, *Detail Berita*).
  - Navigasi menu publik (*Header & Footer Menu*).
- [x] **Task 1.5**: Tambahkan pengujian integrasi model ORM & relasi data di `tests/run.php`.

### Fase 2: Backend API & Controller Admin CMS
- [x] **Task 2.1**: Daftarkan rute admin CMS di `routes/admin.php` di bawah otorisasi Level 2+ (*admin, superadmin*):
  - Kategori & Tags: `GET/POST /admin/cms/categories`, `PUT/DELETE /admin/cms/categories/{id}`, `GET/POST /admin/cms/tags`, `DELETE /admin/cms/tags/{id}`
  - Berita: `GET/POST /admin/cms/news`, `PUT/DELETE /admin/cms/news/{id}`, upload thumbnail
  - Halaman: `GET/POST /admin/cms/pages`, `PUT/DELETE /admin/cms/pages/{id}`
  - Komentar: `GET /admin/cms/comments`, `PUT /admin/cms/comments/{id}/status`, `DELETE /admin/cms/comments/{id}`
  - Menu Publik: `GET/POST /admin/cms/menus`, `PUT/DELETE /admin/cms/menus/{id}`, reorder
- [x] **Task 2.2**: Implementasi `App\Controllers\Admin\CmsController`:
  - Validasi input & slug generator otomatis.
  - Penanganan upload thumbnail berita ke `public/uploads/news/`.
  - Logging aktivitas via `ActivityLogger::log(...)` pada setiap aksi CMS.
  - Notifikasi moderasi komentar via `ActivityLogger::notify(...)`.
- [x] **Task 2.3**: Daftarkan menu "CMS & Konten Publik" pada pohon menu admin (`database/seed.php`).

### Fase 3: Antarmuka Desktop Windows CMS (`core.js`)
- [x] **Task 3.1**: Tambahkan deteksi action modul CMS pada `openWindow` di `public/assets/js/window/core.js` & `resources/assets/js/window/core.js`:
  - `open_cms_news`: Jendela Manajemen Berita.
  - `open_cms_pages`: Jendela Manajemen Halaman & Pengaturan Komentar.
  - `open_cms_taxonomy`: Jendela Kategori & Tags.
  - `open_cms_comments`: Jendela Moderasi Komentar Publik.
  - `open_cms_menus`: Jendela Navigasi Menu Publik.
- [x] **Task 3.2**: Implementasi jendela **Manajemen Berita**:
  - Filter pencarian, kategori, & status publikasi.
  - Form editor berita (Judul, Slug, Kategori, Tags multi-select, Excerpt, Konten, Upload Featured Image, Allow Comments).
- [x] **Task 3.3**: Implementasi jendela **Manajemen Halaman**:
  - Form halaman: Judul, Slug, Pilihan Template (`standard`, `news_index`, `news_single`).
  - Panel kustomisasi komentar khusus halaman: toggle aktif, pilihan gaya layout (*cards*, *threaded*, *minimal*), guest commenting.
- [x] **Task 3.4**: Implementasi jendela **Kategori & Tags**:
  - CRUD Kategori dengan color picker badge.
  - Manajemen Tags cepat.
- [x] **Task 3.5**: Implementasi jendela **Moderasi Komentar**:
  - Tab: Menunggu Persetujuan (*Pending*), Disetujui (*Approved*), Spam, Ditolak.
  - Aksi instan: *Approve*, *Mark Spam*, *Hapus*, dan balas komentar.
- [x] **Task 3.6**: Implementasi jendela **Navigasi Menu Publik**:
  - Pengurutan urutan menu publik (sort order) & pilihan target halaman/kategori.
- [x] **Task 3.7**: Integrasi **TinyMCE 8 WYSIWYG Rich Text Editor**:
  - Pemuatan CDN resmi TinyMCE 8 pada layout dashboard admin.
  - Inisialisasi rich editor fleksibel pada form konten berita (`cms-news-input-content`) dan halaman (`cms-pages-input-content`).
  - Uploader gambar terintegrasi langsung ke endpoint `/admin/cms/news/upload-image`.
  - Penyesuaian z-index dialog TinyMCE agar selalu berada di atas layer window manager desktop.
  - Lifecycle cleanup otomatis saat window ditutup dan fallback ke textarea standar jika offline.

### Fase 4: Routing Website Publik & View Templates
- [x] **Task 4.1**: Routing dinamis publik di `routes/web.php`:
  - Dynamic Catch-All page resolver untuk `/{slug}` dan `/berita/{slug}`.
  - Endpoint submit komentar publik: `POST /news/{id}/comments` ber-CSRF token & honeypot anti-spam.
- [x] **Task 4.2**: Implementasi `App\Controllers\Web\CmsPublicController`:
  - Resolusi halaman dinamis dari database.
  - Query daftar berita berpaginasi untuk template `news_index`.
  - Query detail berita dan komentar yang disetujui untuk template `news_single`.
  - Validasi dan penyimpanan komentar baru (status default: `pending` atau sesuai konfigurasi page).
- [x] **Task 4.3**: Layout & Tampilan Publik:
  - Layout navbar dinamis dari `public_menus`.
  - Tampilan Halaman Standar (`web/pages/standard.php`).
  - Tampilan Halaman Feed Berita (`web/pages/news_index.php`) dengan filter kategori dan pencarian.
  - Tampilan Halaman Detail Berita (`web/pages/news_single.php`) lengkap dengan rendering komentar sesuai `comment_settings` halaman.
  - Form komentar publik responsif dengan proteksi anti-spam.

### Fase 5: Pengujian Otomatis, Keamanan, & Finalisasi
- [x] **Task 5.1**: Penambahan unit & integration tests lengkap di `tests/run.php`:
  - CRUD Kategori & Tags.
  - CRUD Berita & relasi Tags.
  - CRUD Pages & penyimpanan konfigurasi JSON komentar.
  - Siklus komentar publik: submit komentar, moderasi admin, dan tampilan publik.
- [x] **Task 5.2**: Verifikasi sanitasi XSS pada konten HTML artikel dan teks komentar.
- [x] **Task 5.3**: Verifikasi seluruh pengujian lolos 100% via `docker compose exec -T app composer test`.

### Fase 6: Sistem Template Halaman (Content Blueprints & Public Layout Templates)
- [x] **Task 6.1**: Penambahan kolom `layout_template` (`default`, `fullwidth`, `sidebar`, `blank`) pada skema database `pages` dan model ORM `App\Models\Page`.
- [x] **Task 6.2**: Pembuatan View Templates Publik di `resources/views/web/pages/`:
  - `standard.php`: Container kartu terstruktur dengan header breadcrumb.
  - `fullwidth.php`: Canvas lebar penuh tanpa wrapper container kaku (sangat cocok untuk landing page modular).
  - `sidebar.php`: Layout 2 kolom (konten utama + sidebar navigasi halaman terkait & warta terkini).
  - `blank.php`: Minimalis bersih tanpa hero bar atas.
- [x] **Task 6.3**: Pembuatan Katalog Blueprint Konten Siap Pakai di `WindowCore`:
  - *Tentang Kami (About Us)*: Visi, Misi, Nilai Utama, dan Inovasi.
  - *Layanan & Fitur (Services)*: Grid 3 kolom kartu fitur, icon FontAwesome, dan Call-to-Action banner.
  - *Tanya Jawab (FAQ)*: Akordeon tanya jawab interaktif responsif.
  - *Hubungi Kami (Contact Us)*: Info kontak kantor, WhatsApp, email, dan form kirim pesan.
  - *Kebijakan Privasi (Privacy Policy)*: Struktur pasal-pasal formal hukum.
- [x] **Task 6.4**: Integrasi antarmuka pada Desktop Window `open_cms_pages`:
  - Input pilihan *Tata Letak Publik (Layout)*.
  - Dropdown & tombol cepat *"Terapkan Blueprint"* yang langsung mengisi konten ke TinyMCE.
  - Registrasi native `templates` ke menu TinyMCE 8 (`Insert -> Template`).
  - Badge layout template pada tabel daftar halaman.
- [x] **Task 6.5**: Automated Testing di `tests/run.php`:
  - Pengujian end-to-end pembuatan halaman ber-layout `fullwidth`, `sidebar`, `blank`, dan `default` serta resolusi tampilan publik (46 passing tests).

---

## 5. Catatan Riwayat Pembaruan (*Changelog*)

| Tanggal | Fase / Task | Keterangan Pembaruan |
| :--- | :--- | :--- |
| *2026-09-12* | Inisialisasi | Pembuatan dokumen roadmap dan spesifikasi teknis arsitektur CMS. |
| *2026-09-12* | Fase 1 | Selesai implementasi skema DDL 7 tabel CMS, pembuatan 6 Model ORM, seeder data CMS awal, dan 3 rangkaian automated tests integrasi model & relasi data (41 passing tests). |
| *2026-09-12* | Fase 2 | Selesai implementasi `App\Controllers\Admin\CmsController`, pendaftaran 20+ endpoint API CMS di `routes/admin.php`, upload gambar, activity logging, RBAC checks, dan penambahan automated tests (42 passing tests). |
| *2026-09-12* | Fase 3 | Selesai implementasi antarmuka Desktop Window Environment untuk 5 modul CMS (`open_cms_news`, `open_cms_pages`, `open_cms_taxonomy`, `open_cms_comments`, `open_cms_menus`) di `public/assets/js/window/core.js` & `resources/assets/js/window/core.js`. |
| *2026-09-12* | Fase 4 | Selesai implementasi `App\Controllers\Web\CmsPublicController`, perutean website publik (`/`, `/{slug}`, `/berita`, `/berita/{slug}`, `/berita/kategori/{slug}`, `/berita/tag/{slug}`, `POST /news/{id}/comments`), serta view templates responsif (`navbar.php`, `footer.php`, `standard.php`, `news_index.php`, `news_single.php`, `web/home/index.php`) dengan mesin komentar terkustomisasi (cards/threaded/minimal). |
| *2026-09-12* | Fase 5 | Selesai pengujian menyeluruh (45 passing tests, 0 failed) meliputi routing publik dinamis, feed berita, reader berita, counter kunjungan, submit komentar anti-spam (honeypot + XSS sanitization), dan siklus moderasi komentar hingga respon admin. |
| *2026-09-12* | Penyempurnaan Fase 3 | Integrasi resmi TinyMCE 8 WYSIWYG Editor pada form artikel Berita dan Halaman (`core.js`, `dashboard/index.php`, `window.css`) dengan integrasi upload gambar otomatis via `/admin/cms/news/upload-image`, penanganan z-index dialog, dan fallback aman. |
| *2026-09-12* | Fase 6 | Implementasi menyeluruh Sistem Template Halaman: 4 Layout View Publik (`default`, `fullwidth`, `sidebar`, `blank`), 5 Blueprint Konten Siap Pakai di TinyMCE (*Tentang Kami, Layanan, FAQ, Kontak, Privasi*), UI selector desktop window, dan penambahan automated tests (46 passing tests, 100% PASS). |


