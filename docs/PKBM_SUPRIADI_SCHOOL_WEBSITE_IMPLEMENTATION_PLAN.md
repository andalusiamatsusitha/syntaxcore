# PKBM S. SUPRIADI SCHOOL WEBSITE IMPLEMENTATION & TASK ROADMAP
Dokumen Spesifikasi Teknis, Struktur Konten, Desain Antarmuka, dan Lembar Kerja Tugas Website Sekolah PKBM S.Supriadi

---

## 1. Ringkasan & Tujuan Proyek

Website publik SyntaxCore ditransformasikan menjadi **Website Resmi PKBM S.Supriadi** (*Pusat Kegiatan Belajar Masyarakat S. Supriadi*) dengan mencontoh struktur, konten, identitas, dan tata letak persis dari sumber resmi:
**[https://pkbmsupriadi.sch.id/](https://pkbmsupriadi.sch.id/)**.

### Profil Singkat Sekolah:
- **Nama Lembaga**: PKBM S.Supriadi
- **Naungan**: Dinas Pendidikan Kota Malang
- **NPSN**: P9962749 (Terakreditasi)
- **Bentuk Pendidikan**: Pendidikan Nonformal Kesetaraan (Paket A setara SD, Paket B setara SMP, Paket C setara SMA)
- **Kepala Sekolah**: Muh Fajar Mujahid, S.Pd
- **Alamat**: Jl. S.Supriadi IX No.42 RT.13 RW.04, Kel. Sukun, Kec. Sukun, Kota Malang, Jawa Timur 65147
- **Kontak Telepon / WA**: 085954447600
- **Email**: pkbmsupriadi1@gmail.com
- **Warna Tema Utama**: Hijau Edukasi (`#2db700`), Teks Utama (`#333333`), Soft Blue Section (`#E8F5FF`), Footer Gelap (`#111827`)

---

## 2. Struktur Menu Navigasi Bertingkat

Situs mengadopsi navigasi bertingkat persis seperti `pkbmsupriadi.sch.id`:

1. **Beranda** (`/`)
2. **Profil Kami** (Dropdown):
   - Profil Sekolah (`/page/profil-sekolah`)
   - Identitas Sekolah (`/page/identitas-sekolah`)
   - Visi & Misi (`/page/visi-misi`)
   - Sejarah Singkat (`/page/sejarah-singkat`)
   - Struktur Organisasi (`/page/struktur-organisasi`)
   - Staf Pengajar (`/page/staf-pengajar`)
   - Staf Tenaga Kependidikan (`/page/staf-tenaga-kependidikan`)
3. **Fasilitas** (`/page/fasilitas-sekolah`)
4. **Galeri** (`/page/galeri-sekolah`)
5. **Informasi (Warta & Berita)** (`/berita`)
6. **Intrakurikuler** (`/page/ekstrakurikuler`)
7. **Kontak** (`/page/kontak`)
8. **Kelulusan** (`/page/kelulusan`)
9. **Daftar Siswa** (Dropdown Bertingkat):
   - Paket A: Kelas 1, Kelas 2, Kelas 3, Kelas 4, Kelas 5, Kelas 6
   - Paket B: Kelas 7, Kelas 8, Kelas 9
   - Paket C: Kelas 10, Kelas 11, Kelas 12
10. **Online Mandiri** (Tautan Eksternal Google Sites Mandiri)
11. **PPDB 2026** (Tombol Aksi Khusus / Call-To-Action)

---

## 3. Komponen Halaman Beranda (Homepage)

Sesuai dengan `pkbmsupriadi.sch.id`, beranda terdiri dari:
1. **Top Contact Bar**:
   - Informasi telepon, email resmi, dan tautan media sosial (WhatsApp, Instagram `@pkbmssupriadi`, YouTube `@PKBMSUPRIADI`, TikTok `@pkbmssupriadi`) serta tautan Desktop Admin.
2. **Main Navbar (Brand & Header Area)**:
   - Logo resmi PKBM S.Supriadi, nama lembaga, tombol Call-to-Action PPDB 2026 (desktop & mobile), dan tombol toggler navigasi mobile.
3. **Second Navbar (`nav.second-navbar`)**:
   - Khusus menampung `div#pkbmNavbar` yang berisi menu navigasi publik bertingkat dan tautan Warta & Berita, berstatus `sticky-top`. Tombol PPDB dialihkan sepenuhnya ke Main Navbar.
4. **Hero Slider (Carousel 4 Slide)**:
   - Slide 1: *"Belajar dari Lingkungan Sekitar"*
   - Slide 2: *"Semua Berhak Mendapatkan Pendidikan"*
   - Slide 3: *"Pembelajaran Luar Kelas ialah salah satu solusi."*
   - Slide 4: *"Fleksibelnya belajar di PKBM"*
4. **Sambutan Kepala Sekolah & Statistik Sekolah**:
   - Foto & kutipan sambutan Kepala Sekolah (Muh Fajar Mujahid, S.Pd).
   - Indikator Statistik: **12 Guru & Staf**, **332 Siswa**, **16 Rombel**.
5. **Berita, Artikel & Informasi (Warta & Berita Terkini)**:
   - Menampilkan artikel aktual dari database (Ceria SuperCamp, Pelaksanaan TKA Paket A & B, UPK Paket C, Gladi TKA, dsb.).
6. **Staf Pengajar & Guru**:
   - Daftar guru: Adinda Sukma Nur Chaya, S.Pd; ALFINDHA ROSA SANITA, S.Pd; Dimas Arya Agung Nugraha, S.Pd; Ilham Gus Adhiim, M.Pd; Retno Wahyu Kusuma, S.Pd; Zulius Nilam Cahya, S.Pd.
7. **Staf Tenaga Kependidikan**:
   - Kepala Sekolah & Operator Sekolah.
8. **Fasilitas Sekolah**:
   - Laboratorium Komputer dan ruang belajar.
9. **Intrakurikuler & Program Keterampilan**:
   - Computer Class, Cooking Class, English Club, Outing Class, SuperCamp.
10. **Testimoni Alumni**:
    - Ismail (Alumni Paket C 2025)
    - Muhammad Hamza Zidan (Alumni Paket B 2024)
11. **Footer Komprehensif**:
    - Legalitas sekolah (NPSN P9962749, Akreditasi, Naungan Disdik Kota Malang).
    - Tautan Cepat Profil Sekolah.
    - Link Penting Nasional: Dapodikdasmen, Informasi GTK, Informasi NISN, Verval PTK, Verval PD, Google Classroom.
    - Hak Cipta & Powered by SyntaxCore.

---

## 4. Lembar Kerja Tugas (Task Checklist)

- [x] **Tahap 1: Persiapan & Rencana Implementasi**
  - [x] Riset & ekstraksi konten langsung dari `https://pkbmsupriadi.sch.id/` via HTTP fetch
  - [x] Pembuatan dokumen panduan teknis `docs/PKBM_SUPRIADI_SCHOOL_WEBSITE_IMPLEMENTATION_PLAN.md`

- [x] **Tahap 2: Pembaruan Database Seeder CMS (`database/seed.php`)**
  - [x] Update seeding data kategori: Artikel, Berita Pendidikan, Ujian, Ramadhan, Teknologi (kompatibilitas tes), Umum
  - [x] Update seeding data artikel berita otentik dari PKBM S.Supriadi
  - [x] Update seeding halaman-halaman profil sekolah (Profil Sekolah, Identitas Sekolah, Visi & Misi, Sambutan Kepala Sekolah, Fasilitas, Intrakurikuler, Kontak, PPDB)
  - [x] Update seeding menu navigasi publik bertingkat (Beranda, Profil Kami, Fasilitas, Galeri, Informasi, Intrakurikuler, Kontak, Kelulusan, Daftar Siswa Paket A/B/C, Online Mandiri, PPDB 2026)

- [x] **Tahap 3: Pembaruan Layout & Komponen Publik (`resources/views/web/`)**
  - [x] Desain `navbar.php` dengan Top Contact Bar dan branding hijau PKBM S.Supriadi
  - [x] Implementasi arsitektur Two-Tier Navbar: Main Navbar (Header/Branding/PPDB) dan Second Navbar (`sticky-top`) khusus menampung `div#pkbmNavbar`
  - [x] Konfigurasi routing ganda CMS di `routes/web.php` untuk mendukung `/page/{slug}` dan `/{slug}` secara bersamaan
  - [x] Desain `footer.php` dengan identitas resmi PKBM S.Supriadi dan tautan link penting Dapodik / Kemendikdasmen
  - [x] Desain `home/index.php` yang mencakup Hero Slider, Sambutan Kepala Sekolah, Statistik Data Sekolah, Berita Warta, Guru & Tendik, Fasilitas, Intrakurikuler, dan Testimoni
  - [x] Penyesuaian `pages/standard.php`, `sidebar.php`, `fullwidth.php`, `news_index.php`, dan `news_single.php` agar konsisten dengan gaya sekolah

- [x] **Tahap 4: Eksekusi Seeding & Validasi Pengujian**
  - [x] Jalankan seeder database di container app
  - [x] Jalankan pengujian menyeluruh `docker compose exec -T app composer test`
  - [x] Verifikasi kelulusan 100% dari seluruh 46 unit & integration tests

- [x] **Tahap 5: Review & Dokumentasi Final**
  - [x] Perbarui status checklist tugas
  - [x] Laporkan hasil implementasi kepada pengguna

