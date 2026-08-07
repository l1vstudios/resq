# Operasional, Keamanan, dan Troubleshooting

Dokumen ini adalah runbook untuk environment, deployment, monitoring, incident diagnosis, dan risiko implementasi yang perlu diketahui sebelum aplikasi dipakai sebagai sistem peringatan nyata.

## 1. Topologi deployment

### A. Development satu laptop

```text
Browser
├── Laravel http://127.0.0.1:8000
└── Node backend http://127.0.0.1:3100

Node backend
├── Modbus TCP device di LAN, atau
├── MQTT broker lokal/VPS
└── callback ke Laravel lokal
```

Untuk MQTT broker VPS sementara Laravel lokal, jalankan MQTT subscriber Node di laptop. VPS tidak dapat callback ke `127.0.0.1` laptop.

### B. Development LAN dengan RedNode

```text
RedNode/Bliiot ──LAN──> Laravel pada IP laptop/server:8000
      │
      └──RS485──> sensor
```

Laravel harus bind pada interface LAN dan firewall harus mengizinkan IP RedNode.

### C. Production internet/4G

```text
RedNode/M100
├── HTTPS callback/config/heartbeat → Nginx → Laravel/PHP-FPM
└── optional MQTTS → Mosquitto → Node MQTT service → Laravel

Laravel/PHP-FPM → MySQL
Operator → HTTPS → Nginx → Laravel
```

Gunakan HTTPS/MQTTS, token kuat, service manager, database backup, log rotation, dan health monitoring.

## 2. Referensi environment

### Laravel inti

| Variable | Arti | Production |
|---|---|---|
| `APP_NAME` | Nama aplikasi | Set sesuai deployment |
| `APP_ENV` | `local`, `staging`, `production` | `production` |
| `APP_KEY` | Kunci encryption Laravel | Wajib, rahasiakan, backup |
| `APP_DEBUG` | Tampilkan debug detail | `false` |
| `APP_URL` | Base URL aplikasi | HTTPS public URL |
| `DB_*` | Koneksi database | User least privilege |
| `LOG_CHANNEL`, `LOG_LEVEL` | Logging | `stack`, `info`/`warning` sesuai kebutuhan |
| `QUEUE_CONNECTION` | Queue | Saat ini flow utama memakai `sync` |
| `SESSION_DRIVER` | Session web | File/Redis/database sesuai deployment |

### Security dan freshness aplikasi

| Variable | Default kode | Keterangan |
|---|---:|---|
| `PUBLIC_API_TOKEN` | kosong | Lindungi semua `/api/public/*` |
| `PROJECT_MONITORING_FRESH_SECONDS` | `90` | Batas reading/logger fresh pada monitoring/public API |
| `MQTT_CALLBACK_TOKEN` | kosong | Token utama realtime ingest/MQTT |
| `MODBUS_CALLBACK_TOKEN` | kosong | Fallback token Modbus/config/heartbeat |
| `REDNODE_CONFIG_TOKEN` | kosong | Token khusus ambil config |
| `REDNODE_CALLBACK_TOKEN` | kosong | Token khusus heartbeat RedNode |

Samakan token producer dan Laravel. Gunakan nilai random panjang, berbeda antar environment, dan rotasi terencana.

### Node Modbus/MQTT backend

| Variable | Default | Keterangan |
|---|---:|---|
| `MODBUS_BACKEND_PORT` | `3100` | Port Express backend |
| `MODBUS_BACKEND_URL` | kosong | URL yang dipakai browser/UI |
| `MODBUS_CORS_ORIGIN` | terbuka | Origin yang diizinkan |
| `MQTT_AUTOSTART` | `false` | Subscribe saat process start |
| `MQTT_BROKER_URL` | — | `mqtt://` atau `mqtts://` |
| `MQTT_TOPIC` | `resq/telemetry/#` | Subscription pattern |
| `MQTT_USERNAME/PASSWORD` | — | Kredensial broker |
| `MQTT_CONNECT_TIMEOUT_MS` | `10000` | Timeout connect |
| `MQTT_CALLBACK_URL` | `APP_URL` + endpoint | Target Laravel |
| `MQTT_CALLBACK_TOKEN` | — | Bearer callback |
| `MQTT_SENSOR_CODE` | kosong | Sensor fixed optional |

`MODBUS_CORS_ORIGIN=true` berarti permissive. Pada production isi origin aplikasi secara eksplisit dan jangan publikasikan port 3100 langsung.

### RedNode gateway

| Variable | Default | Keterangan |
|---|---:|---|
| `REDNODE_PUBLIC_APP_URL` | `APP_URL` | URL public yang dikirim/dirujuk server |
| `REDNODE_DEVELOPMENT_APP_URL` | contoh LAN | Target tombol Development |
| `REDNODE_PRODUCTION_APP_URL` | contoh VPS | Target tombol Production |
| `REDNODE_CONFIG_URL` | `{APP_URL}/api/rednode/config` | Ambil config |
| `REDNODE_CALLBACK_URL` | `{APP_URL}/api/realtime-sensor-status` | Kirim telemetry |
| `REDNODE_HEARTBEAT_URL` | `{APP_URL}/api/rednode/heartbeat` | Kirim health |
| `REDNODE_LOGGER_CODE` | kosong/rekomendasi auto | Override logger |
| `REDNODE_DEVICE_UID` | auto/fallback | Identity stabil, sangat disarankan |
| `REDNODE_SERIAL_NUMBER` | auto/OS | Serial perangkat |
| `REDNODE_DEVICE_MODEL/VENDOR/LABEL` | fallback gateway | Metadata discovery |
| `REDNODE_FIRMWARE_VERSION` | OS label | Firmware metadata |
| `REDNODE_SERIAL_PORT` | `/dev/ttyAS2` | Port RS485 |
| `REDNODE_BAUD_RATE` | `9600` | Serial baud |
| `REDNODE_DATA_BITS` | `8` | Data bits |
| `REDNODE_STOP_BITS` | `1` | Stop bits |
| `REDNODE_PARITY` | `none` | Parity |
| `REDNODE_TIMEOUT_MS` | `1500` | Timeout read |
| `REDNODE_CONFIG_REFRESH_MS` | `5000` | Refresh config |
| `REDNODE_HEARTBEAT_MS` | `1000` | Heartbeat minimum interval |
| `REDNODE_POLL_INTERVAL_MS` | `1000` | Fallback polling |
| `REDNODE_HTTP_TIMEOUT_MS` | `10000` | Digunakan runtime env SSH |
| `REDNODE_MQTT_ENABLED` | `false` | Optional dual publish |
| `REDNODE_MQTT_*` | fallback MQTT umum | Broker/topic/credential |

### SSH dan scan

| Variable | Default | Keterangan |
|---|---:|---|
| `REDNODE_SSH_HOST` | kosong | Fallback remote host |
| `REDNODE_SSH_PORT` | `22` | SSH port |
| `REDNODE_SSH_USER` | `root` | SSH user |
| `REDNODE_SSH_PASSWORD` | kosong | Fallback password |
| `REDNODE_GATEWAY_PATH` | `/root/rednode-gateway` | Folder remote |
| `REDNODE_SCAN_RESPONSE_TIMEOUT_MS` | `300` | Response timeout scan |
| `REDNODE_SCAN_DELAY_MS` | `80` | Delay antar slave scan |

Data Logger-specific `remote_*` di database mengalahkan fallback SSH environment.

## 3. Menjalankan process development

Terminal web:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal asset bila sedang mengubah frontend:

```bash
npm run dev
```

Terminal Modbus/MQTT backend:

```bash
npm run modbus:server
```

Untuk MQTT autostart pada Linux/macOS, script package tersedia:

```bash
npm run mqtt:gateway
```

Script tersebut menggunakan sintaks environment Unix. Pada Windows PowerShell gunakan:

```powershell
$env:MQTT_AUTOSTART='true'
node modbus-server/server.js
```

Atau set `MQTT_AUTOSTART=true` di `.env`, lalu jalankan `node modbus-server/server.js`.

## 4. Provision RedNode/Bliiot

### File yang diperlukan

Salin isi relevan folder `rednode-gateway/` ke default `/root/rednode-gateway`:

```text
gateway.js
package.json
package-lock.json
test-pin-led.js
test-ports.js
.env
```

Install dependency pada arsitektur perangkat. Archive `rednode-gateway-node_modules.tgz` ada di repository untuk deployment tertentu, tetapi reinstall `npm ci --omit=dev` lebih aman bila perangkat memiliki akses registry dan versi Node yang kompatibel.

### Environment minimal

```dotenv
APP_URL=https://resq.example.com
REDNODE_CONFIG_URL=https://resq.example.com/api/rednode/config
REDNODE_CALLBACK_URL=https://resq.example.com/api/realtime-sensor-status
REDNODE_HEARTBEAT_URL=https://resq.example.com/api/rednode/heartbeat

REDNODE_DEVICE_UID=RN-BL118-0001
REDNODE_SERIAL_NUMBER=BL118-0001

REDNODE_CONFIG_TOKEN=TOKEN_CONFIG
REDNODE_CALLBACK_TOKEN=TOKEN_CALLBACK
```

Jika Laravel memakai satu token fallback (`MQTT_CALLBACK_TOKEN`), gateway config/callback token dapat memakai nilai yang sama. Memisahkan token config dan callback memberi rotasi serta scope lebih baik.

### Jalankan manual pertama kali

```bash
cd /root/rednode-gateway
npm run gateway
```

Verifikasi log:

- config URL benar;
- logger berhasil di-resolve atau muncul sebagai discovery;
- serial port terbuka;
- sensor code/address/function sesuai;
- callback dan heartbeat sukses;
- tidak ada process kedua yang memegang port serial.

Setelah lolos smoke test, gunakan systemd/OpenRC/supervisor sesuai OS perangkat. Tombol web juga dapat menjalankan proses dengan `nohup` dan PID file per logger, tetapi service manager lebih cocok untuk restart setelah boot/crash.

## 5. Deployment Laravel production

Urutan generik:

1. deploy source release yang immutable;
2. siapkan `.env`/system environment tanpa commit secret;
3. install dependency production;
4. build frontend;
5. backup database;
6. jalankan migration;
7. cache view bila diperlukan;
8. reload PHP-FPM/web server;
9. restart process Node terkait;
10. jalankan health/smoke test.

Contoh command:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan view:cache
```

Jangan langsung menjalankan `php artisan optimize`/`config:cache` tanpa verifikasi. Banyak logic project memanggil `env()` langsung di controller. Saat config cached, value `.env` yang tidak menjadi system environment dapat tidak tersedia. Beberapa URL RedNode memiliki helper yang membaca `.env` manual, tetapi tidak semua variable memakai helper tersebut.

Arah perbaikan: pindahkan semua custom environment ke `config/resq.php`, gunakan `config()` pada runtime, lalu test config cache.

### Web server

- document root harus `public/`, bukan repository root;
- paksa HTTPS;
- batasi ukuran body sesuai kebutuhan;
- set forwarded headers dengan benar;
- blok akses `.env`, `.git`, log, backup, dan source;
- jangan proxy port 3100 ke internet kecuali benar-benar diperlukan dan dilindungi.

### Permission

User PHP-FPM perlu write ke `storage/` dan `bootstrap/cache/`, bukan seluruh repository. Node service hanya perlu read `.env` dan akses resource/network yang relevan.

## 6. Backup dan recovery

Backup minimum:

- database MySQL;
- `APP_KEY` dan secret environment di secret manager;
- konfigurasi deployment/service;
- certificate/key referensi eksternal;
- `.env` gateway perangkat atau inventory equivalent.

`APP_KEY` sangat penting karena password SSH dalam `data_loggers.remote_ssh_password` dan `connectivity_configs.rednode_ssh_password` dienkripsi dengan key tersebut. Kehilangan/merotasi key tanpa migrasi membuat ciphertext lama tidak dapat dibaca.

Karena telemetry adalah snapshot, backup database juga tidak menyediakan histori pembacaan yang tidak pernah disimpan. Bila audit/histori diperlukan, rancang penyimpanan append-only eksternal.

Uji restore secara berkala, bukan hanya keberadaan file backup.

## 7. Logging dan observability

### Laravel

Default log:

```text
storage/logs/laravel.log
```

Pantau:

- 403 callback/config;
- 422 unknown sensor/logger/validation;
- exception canonical mapping;
- SSH timeout/login/command error;
- query/database error.

### Node MQTT/Modbus server

stdout/stderr service memuat:

- port listen;
- MQTT connect/subscribe/message/callback;
- Modbus connection/read errors;
- poll stats.

Endpoint `/health` memberi state in-memory.

### RedNode

Saat dijalankan oleh tombol web, log biasanya:

```text
/root/rednode-gateway/gateway-{LOGGER_CODE}.log
/root/rednode-gateway/gateway-{LOGGER_CODE}.pid
```

Pantau config fetch, serial open, sensor read, callback, heartbeat, dan reconnect MQTT.

### Metrics yang sebaiknya ditambahkan

- heartbeat age per logger;
- reading age per sensor;
- callback success/error/latency;
- Modbus error per slave/register;
- discovery belum di-claim;
- alert aktif dan durasinya;
- canonical mapping success/failure;
- process restart count;
- queue depth bila kelak memakai queue.

## 8. Keamanan production

### Wajib sebelum go-live

- Set `APP_DEBUG=false`.
- Set token callback/config/public API yang kuat.
- Ganti akun seeder dan nonaktifkan kredensial default.
- Tinjau apakah public registration perlu dinonaktifkan; `Auth::routes()` saat ini mengaktifkan register.
- Gunakan TLS untuk web dan MQTT.
- Batasi CORS dan port backend Node.
- Batasi SSH sumber IP, gunakan user non-root/key-based auth bila memungkinkan.
- Terapkan role/permission; auth saja tidak membedakan operator dan admin.
- Simpan secret di secret manager/system environment, bukan repository/log.
- Audit endpoint lama di luar middleware auth.

### Secret storage yang perlu dipahami

- `DataLogger::remote_ssh_password`: encrypted cast, aman relatif bila `APP_KEY` aman.
- `ConnectivityConfig::rednode_ssh_password`: encrypted cast.
- `DeviceCredential::device_token` dan `mqtt_password_hash`: **tidak** memiliki hash/encrypted cast saat ini. Nama `mqtt_password_hash` tidak membuktikan value di-hash; controller menyimpan string input apa adanya.
- MQTT password pada `.env`: plaintext secret file; lindungi permission.

### Endpoint berisiko tinggi

- Node Express port 3100 dapat connect/read/write Modbus tanpa auth.
- Mini Server melakukan network scan dari server.
- RedNode test/control menjalankan command remote via SSH.
- `update-profile/{id}` dan `update-password/{id}` berada di luar auth group route dan controller profile tidak melakukan authorization terhadap target ID.
- Catch-all route dapat merender view template yang ada tanpa auth.
- Public/read/device API terbuka ketika token kosong.

Pisahkan management network, tambahkan authorization/policy, dan nonaktifkan route yang tidak diperlukan.

## 9. Troubleshooting

### Aplikasi menampilkan dummy data

Gejala: dashboard tetap berisi Padang/Jakarta sample walau database kosong.

Cek:

```bash
php artisan migrate:status
php artisan tinker
```

Pastikan koneksi `.env` menunjuk database yang benar dan tabel `resq_projects` ada. Setelah mengubah `.env`, jalankan `php artisan config:clear` bila config pernah dicache.

### Login gagal

- Pastikan seeder berjalan.
- Cek email `sentinaladmin@resq.com` (ejaan source memang “sentinal”).
- Jalankan `php artisan db:seed --class=AdminUserSeeder` hanya pada environment yang sesuai.
- Jangan mengandalkan password default di production.

### Gateway mendapat 403

- Cocokkan token berdasarkan precedence endpoint.
- Pastikan header berbentuk `Authorization: Bearer ...`.
- Pastikan process Node membaca `.env` dari working directory yang benar.
- Restart process setelah mengubah `.env`.
- Clear config Laravel bila diperlukan.

### Gateway mendapat 422/404 logger tidak dikenal

- Buka Data Loggers → Detected Gateway Devices.
- Claim discovery menjadi Data Logger.
- Isi `REDNODE_DEVICE_UID`/serial number stabil.
- Pastikan `logger_code` tidak typo.
- Bila lebih dari satu logger memakai IP sama, jangan mengandalkan IP; kirim identity unik.

### Config sukses tetapi sensor kosong

- Sensor harus memiliki Slave ID dan address.
- Sensor harus berada pada monitoring station Data Logger tersebut.
- Bila `monitored_sensor_ids` diisi, sensor harus terpilih.
- Pastikan sensor memilih `data_logger_id` yang benar.
- Cek status/migration field `data_logger_id`.

### Serial port tidak dapat dibuka

- Pastikan port benar untuk pasangan pin BL118.
- Pastikan user process punya permission device.
- Pastikan tidak ada gateway/test process lain memegang port.
- Cek baud/data bits/stop bits/parity.
- Jalankan test pin/port dengan gateway utama dihentikan terkontrol.

### Modbus timeout/illegal address

- Cocokkan Slave ID dan FC03 vs FC04.
- Pastikan address basis datasheet (0-based vs 1-based).
- Pastikan quantity cukup untuk type (`float32` biasanya 2 register).
- Kurangi polling rate atau tambah timeout pada bus panjang.
- Periksa termination/bias/polarity RS485.
- Test satu sensor/slave terlebih dahulu.

### Nilai salah sangat besar/kecil

- Periksa signed/unsigned dan float/int.
- Periksa byte/word order.
- Pastikan scale/offset hanya diterapkan sekali.
- Bandingkan raw register pada test port dengan nilai alat referensi.
- Untuk weather station, cocokkan urutan `weather_parameters` dengan register.

### Telemetry masuk tetapi dashboard tidak berubah

- Pastikan `sensor_code` payload/topic cocok persis.
- Periksa response callback 200.
- Cek record `sensors.value/status/last_seen_at` dan `telemetry_readings`.
- Dashboard map endpoint no-cache, tetapi frontend tetap melakukan polling; cek browser network/console.
- Marker butuh koordinat station/workspace/provinsi.

### Status selalu Data Lama

- Sinkronkan NTP/timezone server dan gateway.
- Cek `received_at` dan `last_seen_at`.
- Pastikan heartbeat lebih cepat dari freshness window.
- Naikkan `PROJECT_MONITORING_FRESH_SECONDS` hanya bila interval sensor memang lebih lambat; jangan menutupi gateway mati.

### Threshold tidak sesuai rule

Evaluator hanya mengambil angka pertama dan memakai operator `>`. String seperti `>=`, `<`, rentang, atau MMI tidak benar-benar diparse. Gunakan threshold numerik sederhana sementara, atau implementasikan evaluator terstruktur sebelum mengandalkan rule kompleks.

### Start dari web sukses tetapi process tetap mati

Dalam fallback tanpa SSH, web hanya mengubah config runtime. Harus ada gateway process yang masih hidup untuk mengambil config. Agar dapat menyalakan process yang benar-benar mati, lengkapi SSH atau gunakan service manager dengan auto-restart.

### MQTT masuk broker tetapi tidak masuk Laravel

1. cek broker dengan `mosquitto_sub`;
2. cek Node MQTT process/subscription topic;
3. cek callback URL dapat dijangkau dari process Node;
4. cek token;
5. cek `sensor_code` pada topic/payload;
6. cek response Laravel dan log.

Panduan VPS/M100 yang lebih rinci tersedia di [TUTORIAL_MQTT_VPS_M100.txt](TUTORIAL_MQTT_VPS_M100.txt).

## 10. Temuan penting dan utang teknis

Prioritas berikut berasal dari pembacaan kode saat dokumentasi dibuat, bukan hasil penetration test formal.

### P0 — sebelum sistem dipercaya untuk keselamatan/production

1. **Authorization endpoint profile**: update profile/password memakai target ID dari URL dan route di luar auth group; tambahkan auth dan policy/self-check.
2. **Endpoint device/public dapat terbuka**: token kosong berarti tidak ada proteksi.
3. **Node Modbus write tanpa auth**: jangan expose port 3100; tambahkan auth/network isolation.
4. **Default admin dan public registration**: ganti/nonaktifkan sesuai kebijakan.
5. **Response plan belum automation**: flag SMS/siren bukan jaminan tindakan lapangan.
6. **Tidak ada histori telemetry**: audit kejadian dan trend tidak tersedia dari snapshot.
7. **Alert evaluator terbatas**: hanya `>` numeric, tanpa hysteresis/debounce/duration/multi-level.

### P1 — risiko data/integrasi

1. **Potensi double scaling canonical**: RedNode mengirim `value` yang sudah diskalakan, sementara canonical mapping dapat menerapkan scale/offset lagi bila `raw_value` tidak dikirim.
2. **Byte order belum konsisten**: metadata mapping memiliki `byte_order`, tetapi decoder RedNode utama belum sepenuhnya menerapkan semua urutan register.
3. **Weather station sederhana**: satu register per parameter dan satu scale/offset; banyak perangkat membutuhkan mapping/type/unit per parameter.
4. **Config cache risk**: custom environment dipanggil langsung dengan `env()` di runtime.
5. **Credential naming misleading**: `mqtt_password_hash` tidak otomatis di-hash/encrypt.
6. **Status stale dashboard**: danger map dapat tetap aktif dari snapshot lama.
7. **Idempotency terbatas**: callback tidak memakai event ID; retry hanya menimpa snapshot.

### P2 — maintainability dan kualitas

1. `DeviceSetupController` sangat besar dan mencampur HTTP, domain, network, SSH, serta process control.
2. Method monitoring ganda di `ProjectSetupController` tidak dipakai oleh route aktif.
3. Projection data diduplikasi antara Project dan Registered controller.
4. Daftar sensor type/weather parameter hard-coded di beberapa tempat.
5. `Sensor::$casts` menduplikasi key `weather_parameters`.
6. Seeder canonical memiliki karakter unit mojibake seperti `Â°C`/`Â°`.
7. Test suite belum menutup flow nyata dan example feature kemungkinan tidak sejalan dengan auth redirect.
8. Banyak view demo Skote serta catch-all menambah surface yang tidak perlu.
9. Naming RESQ/Sentinel/Sentinal tidak konsisten.
10. Tidak ada CI/CD definition yang terlihat di repository.

## 11. Roadmap perbaikan yang disarankan

Urutan rasional:

1. Tutup route/auth/token/CORS exposure dan ganti default credential.
2. Buat contract test gateway → Laravel dan test alert/canonical.
3. Perbaiki raw/display/numeric payload untuk mencegah double transform.
4. Pisahkan append-only telemetry history dari current sensor snapshot.
5. Implementasikan alert engine terstruktur dengan state/hysteresis dan audit.
6. Implementasikan response workflow idempotent dengan acknowledgement, retry, dan escalation.
7. Extract service dari controller besar dan hilangkan implementasi duplikat.
8. Pindahkan custom env ke config agar config cache aman.
9. Tambahkan observability, CI, backup/restore test, dan deployment runbook environment-specific.

## 12. Checklist go-live minimum

- [ ] Semua P0 telah ditangani atau risk acceptance tertulis.
- [ ] HTTPS/MQTTS dan certificate renewal diuji.
- [ ] Token berbeda per environment dan rotasi diuji.
- [ ] Default admin/public registration diamankan.
- [ ] Database, `APP_KEY`, dan secret backup dapat direstore.
- [ ] Gateway auto-start/restart setelah power loss.
- [ ] NTP server dan gateway benar.
- [ ] Sensor diuji dengan nilai referensi dan threshold boundary.
- [ ] Kegagalan kabel, broker, internet, server, dan power disimulasikan.
- [ ] Operator memahami beda Offline, Data Lama, Normal, dan Awas.
- [ ] Jalur peringatan nyata memiliki acknowledgement/fallback manual.
- [ ] Log/metrics/alert operasional dipantau.
- [ ] Dokumentasi konfigurasi per site/logger disimpan di inventory yang aman.
