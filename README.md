# RESQ Early Warning System (Sentinel)

RESQ Early Warning System adalah platform Laravel untuk mengonfigurasi proyek pemantauan bencana, menerima telemetry sensor, memantau Data Logger/RedNode, menormalisasi data ke canonical database, dan menampilkan kondisi terkini pada dashboard peta.

> Di source code masih ada tiga penamaan: **RESQ**, **Sentinel**, dan salah eja **Sentinal**. Dokumentasi memakai nama “RESQ Early Warning System (Sentinel)” tanpa mengubah identifier yang sudah digunakan aplikasi.

## Komponen utama

| Komponen | Teknologi | Tanggung jawab |
|---|---|---|
| Web dan API | PHP 8.2+, Laravel 12, Blade | Login, konfigurasi proyek/perangkat, ingest telemetry, dashboard, public API |
| Database | MySQL (konfigurasi bawaan) | Master proyek, perangkat, snapshot telemetry, canonical data |
| Frontend | Bootstrap/Skote, Blade, JavaScript, Vite 4 | Form konfigurasi, tabel, peta, live monitoring |
| Modbus/MQTT backend | Node.js, Express | Uji Modbus TCP, polling, subscribe MQTT, meneruskan data ke Laravel |
| RedNode gateway | Node.js, `modbus-serial`, optional MQTT | Polling RS485 Modbus RTU di perangkat BL118/Bliiot, heartbeat, callback telemetry |

## Mulai cepat

Persyaratan minimum: PHP 8.2, Composer 2, Node.js LTS, npm, dan MySQL.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Untuk Windows PowerShell, salin environment file dengan `Copy-Item .env.example .env`. Sesuaikan koneksi `DB_*` sebelum menjalankan migrasi.

Login hasil seeder hanya untuk development:

- Email: `sentinaladmin@resq.com`
- Password: `12345678`

Ganti password tersebut segera dan jangan menjalankan kredensial bawaan di production.

## Dokumentasi

Mulai dari [Pusat Dokumentasi](docs/README.md). Urutan baca yang direkomendasikan:

1. [Panduan Lengkap dan Flow Aplikasi](docs/01-PANDUAN-LENGKAP.md)
2. [Arsitektur dan Model Data](docs/02-ARSITEKTUR-DAN-DATA.md)
3. [Panduan Pengembangan dan Referensi API](docs/03-PENGEMBANGAN-DAN-API.md)
4. [Operasional, Keamanan, dan Troubleshooting](docs/04-OPERASIONAL-KEAMANAN-TROUBLESHOOTING.md)
5. [Tutorial MQTT VPS dan M100](docs/TUTORIAL_MQTT_VPS_M100.txt)

## Proses yang dapat dijalankan

```bash
# Frontend development
npm run dev

# Build aset production
npm run build

# Backend uji Modbus TCP + MQTT subscriber, port default 3100
npm run modbus:server

# Gateway RS485 versi root project/legacy
npm run gateway

# Gateway yang dipasang pada RedNode/Bliiot
npm run rednode:gateway

# Utilitas diagnosis port serial BL118
npm run rednode:test-pins
npm run rednode:test-ports
```

Laravel, Modbus/MQTT backend, dan RedNode gateway adalah proses terpisah. Jalankan hanya proses yang diperlukan oleh topologi deployment Anda.

## Pengujian

```bash
php artisan test
npm run build
```

Test suite saat ini masih berupa contoh minimal dan belum menjadi jaring pengaman untuk alur telemetry/perangkat. Lihat panduan pengembangan sebelum melakukan perubahan berisiko.

## Catatan production penting

- Isi token callback/config/public API; bila kosong endpoint terkait menerima request tanpa bearer token.
- Pertahankan `APP_KEY`; password SSH Data Logger dienkripsi dengan key tersebut.
- Aplikasi saat ini menyimpan **snapshot terbaru per sensor**, bukan histori telemetry lengkap.
- Evaluasi threshold saat ini hanya membandingkan `nilai > angka threshold` pertama yang ditemukan.
- Jangan mengekspos port backend Node `3100` langsung ke internet tanpa reverse proxy, TLS, dan kontrol akses.
