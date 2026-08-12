# Production Database Deployment Guide (PostgreSQL)

## Ringkasan

Panduan ini menjelaskan cara melakukan deployment struktur dan data database dari environment lokal (MySQL) ke production server dengan PostgreSQL menggunakan Laravel migrations dan PostgreSQL-compatible data dump.

## Prasyarat

- Access SSH ke production server
- Database PostgreSQL di production server sudah berjalan dan accessible
- Laravel project sudah ter-install di production
- Konfigurasi `.env` production sudah valid dengan driver PostgreSQL

## Langkah-Langkah Deployment

### 1. Konfigurasi Database Connection di Production

Edit file `.env` di production server:

```bash
DB_CONNECTION=pgsql
DB_HOST=<postgres-host>
DB_PORT=5432
DB_DATABASE=resq
DB_USERNAME=<postgres-user>
DB_PASSWORD=<postgres-password>
DB_SSLMODE=prefer
```

### 2. Push Code & Database Files ke Repository

```bash
# Dari development machine lokal
git add database/
git add public/db-export/
git commit -m "chore: add production database migration and PostgreSQL data export"
git push origin main
```

### 3. Pull Code di Production Server

```bash
ssh user@production-server
cd /path/to/resq

# Pull latest code
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev
```

### 4. Create Database di PostgreSQL

```bash
# Login ke PostgreSQL sebagai superuser
psql -h <postgres-host> -U postgres

# Buat database baru
CREATE DATABASE resq OWNER <postgres-user>;

# Exit psql
\q
```

### 5. Jalankan Migrations (Membuat Struktur)

```bash
php artisan migrate --force
```

**Output yang diharapkan:**
```
Migrating: 2014_10_12_000000_create_users_table
Migrated:  2014_10_12_000000_create_users_table (xx.xxs)
[... lebih banyak migrations ...]

39 migrations executed successfully
```

**Catatan:** Laravel migrations otomatis mendeteksi driver PostgreSQL dan generate SQL yang sesuai. Tipe data seperti `enum()` akan di-convert ke PostgreSQL `TEXT` dengan constraint (atau CREATE TYPE jika diperlukan).

### 6. Import Data dari Local MySQL Dump

```bash
# Opsi A: Menggunakan PostgreSQL CLI (Recommended)
psql -h <postgres-host> -U <postgres-user> -d resq -f public/db-export/resq_postgres_data.sql

# Opsi B: Kirim file dump ke server terlebih dahulu
scp public/db-export/resq_postgres_data.sql user@production-server:/tmp/
ssh user@production-server
psql -h localhost -U <postgres-user> -d resq -f /tmp/resq_postgres_data.sql
```

**Output yang diharapkan:**
```
INSERT 0 14
INSERT 0 1
INSERT 0 1
[... lebih banyak INSERT ...]
```

### 7. Verifikasi Data di Production

```bash
# Check table counts
php artisan tinker
>>> DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename")
>>> DB::table('users')->count()
>>> DB::table('sensors')->count()

# Atau gunakan psql CLI
psql -h <postgres-host> -U <postgres-user> -d resq

# Lihat list tabel
\dt

# Count rows di beberapa tabel penting
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM sensors;
SELECT COUNT(*) FROM canonical_parameters;

# Exit psql
\q
```

## File-File Penting

| File | Deskripsi |
|------|-----------|
| `database/migrations/` | Semua migration files (39 files) — kompatibel dengan MySQL dan PostgreSQL |
| `database/seeders/` | Seeder files untuk data demo |
| `public/db-export/resq_postgres_data.sql` | PostgreSQL-compatible data dump (data lokal MySQL) |
| `app/Console/Commands/ExportPostgresDump.php` | Artisan command untuk generate PostgreSQL dump |

## Catatan Kompatibilitas MySQL ↔ PostgreSQL

### Tipe Data yang Sudah Dihandle

| MySQL | PostgreSQL | Catatan |
|-------|------------|---------|
| INT, BIGINT | INTEGER, BIGINT | ✓ Direct mapping |
| VARCHAR | CHARACTER VARYING | ✓ Direct mapping |
| TEXT | TEXT | ✓ Direct mapping |
| JSON | JSONB | ✓ Direct mapping |
| ENUM | TEXT + CHECK | ✓ Laravel handles via blueprint |
| TINYINT(1) | BOOLEAN | ✓ Converted in export script |
| DECIMAL | NUMERIC | ✓ Direct mapping |
| TIMESTAMP | TIMESTAMP | ✓ Direct mapping |
| DATETIME | TIMESTAMP | ✓ Direct mapping |

### Migration Drivers

Migration files menggunakan conditional logic untuk operasi database-specific:

```php
// Contoh dari 2026_08_10_150000_expand_sensor_and_telemetry_value_columns.php
if (DB::getDriverName() !== 'mysql') {
    return;  // Skip di PostgreSQL
}
// Raw MySQL SQL di sini...
```

Ini memastikan migration tidak gagal saat dijalankan di PostgreSQL.

## Troubleshooting

### Error: "FOREIGN KEY constraint fails"
- Solusi: Pastikan semua parent table sudah ada sebelum insert data child
- Command export PostgreSQL sudah handle ordering, cek urutan INSERT di file

### Error: "Column does not exist" atau "relation does not exist"
- Solusi: Pastikan migrations dijalankan SEBELUM data import
- Verifikasi: `php artisan migrate:status`

### Error: "could not connect to server"
- Solusi: Cek PostgreSQL server running dan accessible
- Test: `psql -h <host> -U <user> -d postgres`

### Error: "permission denied for schema public"
- Solusi: Grant privilege ke user PostgreSQL
  ```sql
  GRANT USAGE ON SCHEMA public TO <postgres-user>;
  GRANT CREATE ON SCHEMA public TO <postgres-user>;
  ```

### Error: "duplicate key value violates unique constraint"
- Solusi: Data duplikat atau sequence ID belum di-reset
- Reset PostgreSQL sequences:
  ```bash
  php artisan tinker
  >>> DB::select("SELECT setval(pg_get_serial_sequence(tablename, 'id'), MAX(id)) FROM information_schema.tables WHERE schemaname = 'public';")
  ```

## Database Backup Sebelum Deploy

```bash
# Backup PostgreSQL database ke file
pg_dump -h <postgres-host> -U <postgres-user> -d resq > resq_backup_$(date +%Y%m%d_%H%M%S).sql

# Simpan file backup aman
```

## Restore dari Backup (Disaster Recovery)

```bash
# Drop dan recreate database
psql -h <postgres-host> -U postgres -c "DROP DATABASE resq;"
psql -h <postgres-host> -U postgres -c "CREATE DATABASE resq OWNER <postgres-user>;"

# Restore dari backup
psql -h <postgres-host> -U <postgres-user> -d resq -f resq_backup_20260812_120000.sql
```

## Notes untuk Tim DevOps

- PostgreSQL menggunakan schema `public` secara default untuk semua tabel
- Sequences (AUTO_INCREMENT) di Laravel akan otomatis di-create sebagai `tablename_id_seq`
- ENUM types di PostgreSQL lebih strict — pastikan nilai enum sesuai dengan migration definition
- JSON fields PostgreSQL mendukung JSONB (binary) secara default — lebih efficient
- Connection pooling recommended untuk production (gunakan PgBouncer atau pgpool-II)
- Monitor slow queries: `log_statement = 'all'` di postgresql.conf

## Performance Considerations

- Data dump PostgreSQL (~78 KB) akan di-import dalam batch INSERT statements (~500 rows per statement)
- Import time tergantung network latency dan PostgreSQL server performance
- Untuk dataset yang lebih besar (>1 GB), pertimbangkan menggunakan `COPY` command atau parallel import

## Next Steps Setelah Deploy

1. Jalankan application tests:
   ```bash
   php artisan test --env=production
   ```

2. Monitor application logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Verify database connections:
   ```bash
   php artisan tinker
   >>> DB::connection('pgsql')->select("SELECT version()")
   ```

4. Set up regular backups:
   ```bash
   # Cron job untuk daily backup
   0 2 * * * pg_dump -h <host> -U <user> -d resq | gzip > /backups/resq_$(date +\%Y\%m\%d).sql.gz
