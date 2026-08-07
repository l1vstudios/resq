# Panduan Pengembangan dan Referensi API

Dokumen ini menjawab dua kebutuhan: cara mengubah source code dengan aman dan contract endpoint yang perlu dijaga saat Laravel, browser, serta gateway dikembangkan bersama.

## 1. Workflow perubahan kode yang aman

Gunakan urutan ini untuk setiap fitur:

1. Tulis outcome dan contract input/output.
2. Temukan route aktif, bukan hanya method bernama mirip.
3. Petakan tabel/model yang berubah.
4. Buat migration baru bila skema berubah; jangan mengedit migration lama yang sudah mungkin berjalan di production.
5. Perbarui `$fillable`, `$casts`, dan relation model.
6. Perbarui validation dan orchestration controller/service.
7. Perbarui seluruh data projection ke Blade/JSON.
8. Perbarui Blade/JavaScript/gateway consumer.
9. Tambahkan unit/feature test sebelum atau bersama implementasi.
10. Jalankan test, route check, build, dan smoke test end-to-end.
11. Perbarui dokumentasi dan `.env.example` bila contract/config berubah.

Checklist pencarian untuk field baru:

```text
migration → model → controller validation → query/projection → Blade/JS
          → gateway/API payload → seeder/factory → test → docs
```

## 2. Konvensi yang berlaku

### Backend

- Namespace aplikasi: `App\` dengan PSR-4.
- Model Eloquent berada di `app/Models`.
- Route web memakai named route dan CSRF.
- Endpoint device/public berada di `routes/api.php`.
- Controller melakukan `$request->validate()` secara inline.
- CRUD master sering memakai `updateOrCreate()` berdasarkan business code.
- JSON sukses memakai `ok: true`; error umumnya `ok: false` dan status 4xx.
- Tanggal JSON sebaiknya ISO-8601 melalui `toISOString()`.
- Collection Eloquent diubah menjadi row array sebelum dikirim ke Blade.

### Frontend

- Halaman domain: `resources/views/modules/{module}/`.
- Layout utama: `layouts.master`.
- Navigasi: `resources/views/layouts/sidebar.blade.php`.
- Route Laravel dibentuk dengan `route()`; jangan hard-code origin bila tidak perlu.
- Banyak behavior masih inline dalam Blade. Pertahankan scope selector/timer agar tidak bocor ke halaman lain.

### Database

- Business identifier (`project_code`, `sensor_code`, `logger_code`) unik dan dipakai integration contract.
- Numeric Modbus addressing masih sebagian disimpan sebagai string pada `sensors` untuk kompatibilitas.
- JSON field harus memiliki Eloquent cast `array`.
- Password SSH Data Logger dan connectivity memakai cast `encrypted`.
- Tabel snapshot tidak boleh diasumsikan append-only.

## 3. Tutorial menambah field pada entitas

Contoh: menambah `installation_notes` pada sensor.

### 3.1 Buat migration

```bash
php artisan make:migration add_installation_notes_to_sensors_table
```

Isi migration baru:

```php
Schema::table('sensors', function (Blueprint $table) {
    $table->text('installation_notes')->nullable()->after('unit');
});
```

Sediakan `down()` yang menghapus kolom. Jalankan:

```bash
php artisan migrate
```

### 3.2 Perbarui model

Tambahkan `installation_notes` ke `$fillable` pada `app/Models/Sensor.php`.

Jika field JSON/date/encrypted, tambahkan cast yang tepat. Text biasa tidak membutuhkan cast.

### 3.3 Perbarui validation

Tambahkan ke `ProjectSetupController::storeSensor()`:

```php
'installation_notes' => ['nullable', 'string', 'max:5000'],
```

Jangan memakai `$request->all()` untuk mass assignment. Gunakan hasil validation.

### 3.4 Perbarui projection dan UI

Tambahkan field pada row sensor yang dibentuk oleh:

- `ProjectSetupController::viewData()`;
- `RegisteredDataController::data()` bila registry perlu menampilkannya;
- API/gateway config hanya jika perangkat memerlukannya.

Tambahkan input dan value edit pada `resources/views/modules/projects/index.blade.php`. Pastikan `old()` dan existing value ditangani.

### 3.5 Tambahkan test

Test minimal:

- field nullable diterima;
- field tersimpan;
- value terlalu panjang ditolak;
- user tanpa auth tidak dapat mengubahnya.

## 4. Tutorial menambah tipe sensor

Contoh: `air_quality` dengan parameter PM2.5.

### 4.1 Tambahkan tipe pada validation dan UI

Saat ini daftar tipe di-hard-code pada `ProjectSetupController::storeSensor()` dan option Blade. Tambahkan `air_quality` pada keduanya. Idealnya, refactor daftar menjadi config/enum agar tidak diduplikasi.

### 4.2 Definisikan decoding

Tentukan dari datasheet:

- function code;
- register address dan quantity;
- signed/unsigned/float;
- byte/word order;
- scale dan offset;
- unit;
- invalid/sentinel value;
- interval aman untuk bus.

Gateway saat ini mendukung data type umum, tetapi dukungan byte order pada `rednode-gateway/gateway.js` lebih terbatas daripada metadata canonical. Bila sensor membutuhkan CDAB/DCBA, tambahkan decoder dan test dengan register fixture yang nilai akhirnya diketahui.

### 4.3 Tambahkan canonical parameter

Bila perlu canonical data, tambahkan `ParticulateMatter25` melalui UI atau `CanonicalParameterSeeder`, lalu buat mapping profile sensor.

Seeder harus idempotent dengan `updateOrCreate`. Periksa encoding unit; source saat ini memiliki beberapa karakter degree yang mojibake.

### 4.4 Tambahkan threshold semantics

Jangan menaruh rule kompleks sebagai string bila evaluator belum memahaminya. Untuk kebutuhan baru, pertimbangkan field terstruktur:

```json
{
  "operator": ">=",
  "warning": 35,
  "critical": 55,
  "hysteresis": 3,
  "minimum_duration_seconds": 60
}
```

Lalu buat evaluator domain tersendiri dan unit test. Pertahankan compatibility untuk field `threshold` lama selama migrasi.

### 4.5 Uji end-to-end

1. Simulasikan register pada device/test server.
2. Pastikan gateway decode raw → engineering value dengan benar.
3. Pastikan callback tidak menerapkan scale dua kali.
4. Pastikan status boundary tepat (sama dengan threshold, di bawah, di atas).
5. Pastikan freshness dan dashboard marker benar.
6. Pastikan unit public API/canonical konsisten.

## 5. Tutorial menambah halaman CRUD

Contoh entitas `MaintenanceTicket`.

### 5.1 Generate komponen

```bash
php artisan make:model MaintenanceTicket -m
php artisan make:controller MaintenanceTicketController
```

### 5.2 Tambahkan skema, model, dan controller

- Migration menentukan FK/index/delete behavior.
- Model menentukan fillable, casts, relations.
- Controller membatasi index/store/update/delete dan melakukan authorization.

Untuk fitur baru, lebih baik gunakan Form Request dan Policy daripada memperbesar `DeviceSetupController`.

```bash
php artisan make:request StoreMaintenanceTicketRequest
php artisan make:policy MaintenanceTicketPolicy --model=MaintenanceTicket
```

### 5.3 Route

Masukkan route ke group `auth`:

```php
Route::resource('maintenance-tickets', MaintenanceTicketController::class)
    ->only(['index', 'store', 'update', 'destroy']);
```

Jika hanya admin, tambahkan middleware/Policy yang benar. Saat ini autentikasi belum sama dengan role-based authorization.

### 5.4 Blade dan sidebar

Buat:

```text
resources/views/modules/maintenance-tickets/index.blade.php
```

Gunakan `@extends('layouts.master')`, `@section('title')`, dan `@section('content')`. Tambahkan named route ke sidebar serta active state dengan `request()->routeIs()`.

### 5.5 Test

Test route auth, authorization, validation, persistence, dan delete behavior. Hindari hanya menguji HTTP 200.

## 6. Tutorial menambah endpoint device

Contoh endpoint kalibrasi `POST /api/sensors/{sensor}/calibration`.

### Contract lebih dahulu

Tentukan:

- siapa caller;
- bearer token yang dipakai;
- rate limit;
- apakah request idempotent;
- validasi payload;
- response/error code;
- audit yang disimpan.

### Route dan controller

Tambahkan route eksplisit di atas catch-all web bila berupa web route; API route tidak terkena catch-all `web.php`.

Untuk device endpoint baru, jangan mengulang logika token di banyak method. Extract middleware seperti `VerifyDeviceToken` atau guard tersendiri. Sampai refactor dilakukan, ikuti precedence token yang terdokumentasi dan gunakan `hash_equals`.

### Service

Letakkan transformasi/calculation pada service, bukan Blade atau controller. Controller seharusnya hanya:

```text
authorize → validate → call service → format response
```

### Idempotency

Telemetry retry dapat terjadi pada jaringan lapangan. Endpoint baru yang membuat record harus menerima event/request ID unik atau memakai key yang membuat retry aman.

## 7. Tutorial mengubah payload telemetry

Contract utama berada di:

- producer: `rednode-gateway/gateway.js` atau `modbus-server/server.js`;
- consumer: `DeviceSetupController::updateRealtimeSensorStatus()`;
- storage: `TelemetryReading`, `Sensor`, dan `CanonicalMappingService`;
- read side: monitoring, dashboard, public API.

Jika menambah property, contoh `quality_code`:

1. tentukan optional/default agar gateway lama tetap bekerja;
2. tambahkan validation API;
3. tentukan apakah masuk snapshot sensor, telemetry, raw payload, atau canonical quality;
4. buat migration/cast jika disimpan;
5. kirim dari kedua gateway yang relevan;
6. tampilkan pada read API/UI hanya setelah fallback tersedia;
7. uji payload versi lama dan baru.

Untuk canonical mapping, producer sebaiknya mengirim keduanya:

```json
{
  "sensor_code": "TMA-JKT-01",
  "raw_value": "1234",
  "numeric_value": 123.4,
  "display_value": "123.40 cm",
  "threshold_exceeded": true,
  "observed_at": "2026-08-07T10:00:00+07:00"
}
```

`raw_value` dipakai transformasi canonical, `display_value` dipakai snapshot/UI, dan `numeric_value` dipakai evaluasi tanpa parsing string bila tersedia.

## 8. Memecah controller besar

`DeviceSetupController` menangani terlalu banyak concern. Arah refactor yang aman:

```text
DeviceSetupController
├── DataLoggerService
├── TelemetryIngestionService
├── RedNodeConfigService
├── RedNodeDiscoveryService
├── RedNodeSshService
├── NetworkScanService
└── AlertEvaluationService
```

Refactor bertahap:

1. buat characterization test untuk method aktif;
2. extract pure logic lebih dulu (threshold, payload mapping);
3. inject service melalui constructor;
4. jaga response JSON sama;
5. baru pindahkan network/SSH side effect;
6. hapus method duplikat setelah route dan caller dipastikan.

## 9. Strategi pengujian

### Kondisi saat ini

Test suite hanya memiliki contoh unit `true` dan feature request `/`. Pada verifikasi 7 Agustus 2026, test feature tersebut gagal karena mengharapkan HTTP `200`, sedangkan route `/` yang dilindungi auth merespons redirect `302`. `RefreshDatabase` di-import tetapi tidak dipakai, sedangkan SQLite in-memory di `phpunit.xml` masih dikomentari.

Artinya, build/test yang hijau belum membuktikan flow perangkat aman.

### Prioritas test yang sebaiknya dibuat

1. `AlertEvaluationServiceTest` untuk parsing/operator/boundary.
2. `CanonicalMappingServiceTest` untuk scale/offset/raw/string.
3. Feature test callback token dan validation.
4. Feature test telemetry snapshot per sensor.
5. Feature test RedNode config memilih logger/sensor yang tepat.
6. Feature test discovery/claim multi logger dan NAT.
7. Feature test public API token/filter/freshness.
8. Gateway unit test decoder register/byte order/weather station.
9. Contract test payload Node → Laravel.

### Database test

Pilih salah satu:

- SQLite in-memory setelah seluruh migration/query dipastikan kompatibel; atau
- database MySQL khusus test untuk parity production.

Jangan menjalankan test dengan kredensial database development/production. Buat `.env.testing` atau environment CI khusus.

### Commands verifikasi

```bash
php artisan route:list --except-vendor
php artisan migrate:status
php artisan test
npm run build
```

Untuk style PHP, dependency menyediakan Laravel Pint:

```bash
./vendor/bin/pint --test
```

Jangan memformat seluruh repository bersamaan dengan perubahan kecil tanpa meninjau diff; worktree bisa berisi perubahan user lain.

## 10. Referensi API

Base API Laravel: `{APP_URL}/api`.

### 10.1 Authentication dan token

| Endpoint | Token yang diperiksa, urutan fallback |
|---|---|
| `POST /realtime-sensor-status` | `MQTT_CALLBACK_TOKEN` → `MODBUS_CALLBACK_TOKEN` |
| `GET /rednode/config` | `REDNODE_CONFIG_TOKEN` → `MODBUS_CALLBACK_TOKEN` → `MQTT_CALLBACK_TOKEN` |
| `POST /rednode/heartbeat` | `REDNODE_CALLBACK_TOKEN` → `MQTT_CALLBACK_TOKEN` → `MODBUS_CALLBACK_TOKEN` |
| `/public/*` | `PUBLIC_API_TOKEN` |

Header:

```http
Authorization: Bearer TOKEN_YANG_SAMA
Accept: application/json
Content-Type: application/json
```

Bila token terkait kosong di Laravel, endpoint menerima request tanpa bearer token. Ini hanya layak untuk development terisolasi.

### 10.2 POST `/api/realtime-sensor-status`

Menerima pembacaan dari MQTT/Modbus/RedNode.

Minimal dengan code:

```json
{
  "sensor_code": "TMA-JKT-01",
  "value": "123.40 cm"
}
```

Payload lengkap yang disarankan:

```json
{
  "sensor_code": "TMA-JKT-01",
  "data_logger_code": "DL-JKT-001",
  "raw_value": "1234",
  "numeric_value": 123.4,
  "display_value": "123.40 cm",
  "parameter_values": [],
  "threshold_exceeded": true,
  "observed_at": "2026-08-07T10:00:00+07:00",
  "payload": {
    "registers": [1234],
    "quality": "valid"
  }
}
```

Aturan:

- wajib salah satu `sensor_id` atau `sensor_code`;
- Data Logger optional melalui ID/code;
- `value`, `display_value`, dan `raw_value` maksimum 255 karakter;
- bila `threshold_exceeded` dikirim, nilai boolean tersebut dipercaya;
- bila tidak, server membandingkan numeric/display dengan sensor threshold.

Response sukses berisi snapshot sensor, canonical observation ID, canonical parameter value, dan parameter values.

Error umum:

- `403`: bearer token salah;
- `422`: validation gagal atau code tidak ada;
- `500`: storage/mapping error.

### 10.3 GET `/api/rednode/config`

Mengirim konfigurasi untuk gateway.

Query optional:

```text
logger_code=DL-JKT-001
device_uid=RN-BL118-0001
serial_number=BL118-0001
logger_model=BL118
firmware_version=1.0.0
hostname=rednode-001
mac_addresses=AA:BB:CC:DD:EE:FF
```

Identity juga dapat dikirim melalui header `X-Rednode-*`. Response utama:

```json
{
  "ok": true,
  "logger_code": "DL-JKT-001",
  "data_logger_id": 10,
  "logger": {},
  "serial": {
    "port": "/dev/ttyAS2",
    "baud_rate": 9600,
    "data_bits": 8,
    "stop_bits": 1,
    "parity": "none",
    "timeout_ms": 1500,
    "monitored_sensor_ids": [1, 2],
    "poll_interval_ms": 1000
  },
  "monitoring": {
    "enabled": true,
    "last_action": "start"
  },
  "callback": { "url": "https://app/api/realtime-sensor-status" },
  "heartbeat": { "url": "https://app/api/rednode/heartbeat" },
  "mqtt": { "enabled": false },
  "sensors": []
}
```

Jika logger belum dikenal dan tidak ada explicit logger code, API mencatat discovery lalu merespons `422`. Operator harus claim device dari Data Loggers.

### 10.4 POST `/api/rednode/heartbeat`

Contoh:

```json
{
  "logger_code": "DL-JKT-001",
  "serial_port": "/dev/ttyAS2",
  "pin_mapping": "PIN 5-6",
  "connected": true,
  "last_error": null,
  "device": {
    "device_uid": "RN-BL118-0001",
    "serial_number": "BL118-0001",
    "hostname": "rednode-001"
  },
  "sensors": [
    {
      "sensor_code": "TMA-JKT-01",
      "numeric_value": 123.4,
      "value": "123.40 cm",
      "threshold_exceeded": true,
      "received_at": "2026-08-07T10:00:00+07:00"
    }
  ]
}
```

Heartbeat memperbarui connectivity health dan juga dapat menyinkronkan sensor/telemetry dari array `sensors`.

### 10.5 Public read API

Endpoints:

```text
GET /api/public/telemetry/latest
GET /api/public/sensors/{sensorCode}/latest
GET /api/public/projects/{projectCode}/live
```

Filter latest:

- `limit` 1–500, default 100;
- `fresh_seconds`, default environment 90;
- `sensor_code`;
- `logger_code`;
- `project_code` atau numeric project ID.

Contoh:

```bash
curl -H "Authorization: Bearer PUBLIC_TOKEN" \
  "https://app.example.com/api/public/telemetry/latest?project_code=PRJ-FLOOD-JKT&limit=50"
```

Response sensor menyertakan display/raw value, status/alert, freshness, waktu, logger/station/project, dan lokasi.

### 10.6 Sanctum user endpoint

```text
GET /api/user
```

Endpoint ini memakai `auth:sanctum` dan terpisah dari bearer token perangkat/public API.

## 11. Web JSON/control endpoints

Endpoint berikut berada di middleware web + auth dan membutuhkan session/CSRF:

| Endpoint | Fungsi |
|---|---|
| `GET /dashboard/map-data` | Cluster/sensor/warning markers dan alert flag |
| `POST /projects/start-monitoring` | Start logger via SSH atau config polling |
| `POST /projects/stop-monitoring` | Stop logger |
| `GET /projects/live-monitoring?project_id=...` | Snapshot logger/sensor project |
| `POST /rednode-serial-config` | Simpan serial config/sensor selection |
| `POST /rednode-control` | Aksi RedNode spesifik |
| `POST /rednode-port-test` | Test port remote |
| `POST /rednode-pin-scan` | Scan slave/pin remote |
| `GET /rednode-status?logger_code=...` | Health/readings RedNode |
| `POST /data-loggers/gateway-mode` | Ubah `.env` remote lalu restart gateway |
| `POST /mini-server/scan` | Ping sweep subnet dari server |

Jangan panggil endpoint web ini dari device tanpa session. Untuk integrasi mesin gunakan endpoint API khusus.

## 12. Backend Node Modbus/MQTT API

Base default: `http://127.0.0.1:3100`. Endpoint ini tidak memiliki authentication.

| Method/path | Fungsi |
|---|---|
| `GET /health` | Connection, poll, MQTT, stats |
| `POST /api/modbus/connect` | Buka Modbus TCP |
| `POST /api/modbus/disconnect` | Tutup connection |
| `POST /api/modbus/read` | Read FC01/02/03/04 |
| `POST /api/modbus/write` | Write FC05/06/15/16 |
| `GET /api/poll/status` | Status satu poll job in-memory |
| `POST /api/poll/start` | Start polling dan optional callback |
| `POST /api/poll/stop` | Stop polling |
| `GET /api/mqtt/status` | Status MQTT |
| `POST /api/mqtt/connect` | Connect/subscribe |
| `POST /api/mqtt/disconnect` | Disconnect |
| `POST /api/mqtt/test-publish` | Publish test payload |

Contoh read:

```json
{
  "host": "192.168.1.50",
  "port": 502,
  "unitId": 1,
  "timeout": 1000,
  "functionCode": "FC03",
  "address": 0,
  "quantity": 2
}
```

Response register berisi `raw`, `uint16`, `int16`, `hex`, `binary`, dan pasangan `float32` bila tersedia.

## 13. Definition of done

Perubahan dianggap selesai jika:

- [ ] route/caller aktif telah diverifikasi;
- [ ] migration forward/down dan model sinkron;
- [ ] validation menolak input buruk;
- [ ] authorization/token/CSRF sesuai jenis caller;
- [ ] old gateway/payload tetap kompatibel atau migration plan tersedia;
- [ ] test baru menutup happy path dan boundary/error penting;
- [ ] `php artisan test` dan `npm run build` diperiksa;
- [ ] live device/simulator smoke test dilakukan untuk perubahan protocol;
- [ ] `.env.example` dan docs diperbarui;
- [ ] tidak ada secret atau data production masuk commit.

Lanjutkan ke [Operasional, Keamanan, dan Troubleshooting](04-OPERASIONAL-KEAMANAN-TROUBLESHOOTING.md).
