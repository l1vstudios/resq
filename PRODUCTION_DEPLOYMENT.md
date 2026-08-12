# Production Database Deployment Guide

## Ringkasan

Panduan ini menjelaskan cara melakukan deployment struktur dan data database dari environment lokal ke production server menggunakan Laravel migrations dan data dump.

## Prasyarat

- Access SSH ke production server
- Database MySQL di production server sudah berjalan
- Laravel project sudah ter-install di production
- Konfigurasi `.env` production sudah valid

## Langkah-Langkah Deployment

### 1. Push Code & Database Files ke Repository

```bash
# Dari development machine lokal
git add database/
git add storage/app/db-export/
git commit -m "chore: add production database migration and data export"
git push origin main
```

### 2. Pull Code di Production Server

```bash
ssh user@production-server
cd /path/to/resq

# Pull latest code
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev
```

### 3. Jalankan Migrations di Production (Membuat Struktur)

```bash
php artisan migrate --force
```

**Output yang diharapkan:**
```
Migrating: 2014_10_12_000000_create_users_table
Migrated:  2014_10_12_000000_create_users_table (xx.xxs)
[... lebih banyak migrations ...]
```

Migrations akan create semua 45 tabel dengan struktur yang sama seperti lokal.

### 4. Import Data dari Local Dump (Mengisikan Data)

```bash
# Opsi A: Menggunakan SQL dump file langsung
mysql -h DB_HOST -u DB_USERNAME -p DB_PASSWORD DB_DATABASE < storage/app/db-export/resq_data_only.sql

# Opsi B: Menggunakan Laravel command (recommended)
php artisan db:seed --class=ProductionDataSeeder
```

**Catatan:** Dump file hanya berisi data INSERT, tidak ada CREATE TABLE statements.

### 5. Verifikasi Data di Production

```bash
# Check table counts
php artisan tinker
>>> DB::select("SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . env('DB_DATABASE') . "' ORDER BY TABLE_NAME")

# Atau gunakan MySQL CLI
mysql -h DB_HOST -u DB_USERNAME -p DB_PASSWORD DB_DATABASE -e "SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'resq' ORDER BY TABLE_NAME"
```

## File-File Penting

| File | Deskripsi |
|------|-----------|
| `database/migrations/` | Semua migration files untuk structure (39 files) |
| `database/seeders/` | Seeder files untuk data awal |
| `storage/app/db-export/resq_full.sql` | Dump lengkap (struktur + data) untuk backup |
| `storage/app/db-export/resq_data_only.sql` | Data-only dump untuk import ke production |

## Workflow Opsi: All-in-One Deployment

Jika ingin deploy struktur dan data sekaligus dari fresh database:

```bash
# Di production server (clean database)
php artisan migrate:fresh --seed --force
```

**Peringatan:** `migrate:fresh` akan DROP semua tabel, gunakan hanya di environment baru!

## Troubleshooting

### Error: "Table already exists"
- Migration sudah berjalan sebelumnya
- Solusi: Cek `migrations` table
  ```bash
  php artisan migrate:status
  ```

### Error: "Foreign key constraint fails"
- Data referencing tabel yang belum ada
- Solusi: Pastikan migrations dijalankan terlebih dahulu, kemudian data import

### Error: "Column not found"
- Struktur data tidak cocok dengan migration
- Solusi: Regenerate dump dari lokal yang sudah fresh

## Rollback (Jika ada error)

```bash
# Revert migrations (struktur)
php artisan migrate:rollback

# Atau rollback hingga batch tertentu
php artisan migrate:rollback --step=5
```

## Database Backup Sebelum Deploy

```bash
# Backup database existing di production
mysqldump -h DB_HOST -u DB_USERNAME -p DB_PASSWORD DB_DATABASE > resq_backup_$(date +%Y%m%d_%H%M%S).sql

# Simpan file backup aman
```

## Notes untuk Tim DevOps

- Semua migrations idempotent (aman dijalankan berkali-kali)
- Migration files checked into version control
- Data dump di-export dengan `--set-gtid-purged=OFF` untuk kompatibilitas
- Recommended: gunakan dedicated deployment user dengan minimal permissions
- Monitor performance saat import data besar (476 baris INSERT statements)

## Kontak & Support

Jika ada issue, check:
1. Laravel documentation: https://laravel.com/docs/migrations
2. MySQL troubleshooting logs
3. aplikasi logs di `storage/logs/`
