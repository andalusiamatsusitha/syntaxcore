# Panduan Restore Database Produksi - PKBM S. Supriadi

Direktori ini berisi cadangan (*dump*) lengkap basis data MySQL untuk website resmi **PKBM S. Supriadi** (*SyntaxCore CMS & Public Engine*).

## Berkas Backup Tersedia
* `latest_production_backup.sql`: Berkas *dump* terbaru siap restore (187 KB, UTF-8 MB4, mencakup skema 13 tabel dan seluruh data otentik).
* `syntaxcore_pkbmsupriadi_backup_*.sql`: Arsip berkas cadangan dengan stempel waktu pembuatan.

---

## 1. Cara Restore di Server Produksi (Menggunakan Docker Compose)

Jika server produksi menjalankan arsitektur Docker Compose yang sama:

```bash
# 1. Pastikan container db sedang berjalan
docker compose up -d db

# 2. Lakukan restore database menggunakan berkas backup
docker compose exec -T db mysql -u root -proot syntaxcore < database/backups/latest_production_backup.sql
```
*(Sesuaikan `-proot` dan nama basis data `syntaxcore` dengan nilai environment `DB_ROOT_PASSWORD` dan `DB_DATABASE` pada server produksi).*

---

## 2. Cara Restore di Server Non-Docker / Standar MySQL

Jika server produksi menggunakan MySQL atau MariaDB yang terinstal langsung di host OS:

```bash
# 1. Buat database kosong jika belum ada
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS syntaxcore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Impor berkas backup
mysql -u root -p syntaxcore < database/backups/latest_production_backup.sql
```

---

## 3. Verifikasi Pasca Restore

Setelah proses restore selesai, pastikan data berhasil masuk:

```bash
# Periksa jumlah tabel (harus ada 13 tabel)
mysql -u [user] -p[pass] syntaxcore -e "SHOW TABLES;"

# Periksa halaman publik yang tersedia (21 halaman PKBM)
mysql -u [user] -p[pass] syntaxcore -e "SELECT id, title, slug, page_type, status FROM pages;"

# Periksa akun administrator desktop
mysql -u [user] -p[pass] syntaxcore -e "SELECT id, name, email, role_id FROM users;"
```

### Kredensial Bawaan Administrator:
* **Email**: `admin@syntaxcore.com`
* **Password**: `admin123`
