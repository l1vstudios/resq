<!-- generated-by: gsd-doc-writer -->
# Alur Lengkap RESQ: Dari Menyiapkan Proyek hingga Simulasi dan Device Nyata

Dokumen ini adalah panduan berbasis teks untuk menjalankan RESQ dari repository baru, membuat struktur proyek dan mapping canonical, menguji aliran data tanpa hardware, lalu beralih ke device nyata ketika perangkat sudah tersedia.

**Baseline kode:** commit `dbd5826` dan implementasi canonical sampai Phase 6.  
**Status Phase 7:** simulator CommonJS otomatis dan release-gate eksternal masih **NOT RUN/BLOCKED**. Bagian simulasi di dokumen ini memakai callback dan verifier yang sudah tersedia; bagian itu tidak boleh dianggap sebagai bukti bahwa seluruh Phase 7 sudah lulus.

## Legenda status

- **VERIFIED repository behavior**: route, command, model, atau service ditemukan pada source saat dokumen dibuat.
- **VERIFY**: langkah yang harus dijalankan operator pada environment tujuan.
- **NOT RUN**: langkah belum dijalankan pada environment tujuan.
- **BLOCKED**: langkah belum dapat dijalankan karena dependency, database, atau device belum tersedia.
- **PASS/FAIL**: hanya diberikan setelah command benar-benar dijalankan dan evidence disimpan.

## Gambaran full flow

```text
REPOSITORY BARU
  |
  +-- Siapkan PHP 8.2-8.4, Composer, Node 20+, npm, dan MySQL
  +-- Install dependency, buat .env, generate APP_KEY
  +-- Jalankan migration dan seed catalog canonical
  +-- Jalankan Laravel dan frontend
  |
  v
KONFIGURASI BISNIS
  |
  +-- Project
  +-- Workspace
  +-- Monitoring Station
  +-- MST Prefix
  +-- Sensor
  +-- Data Logger
  +-- Connectivity
  +-- Device Credential
  |
  v
KONFIGURASI CANONICAL
  |
  +-- Periksa catalog parameter/unit
  +-- Buat mapping profile draft
  +-- Tambah rule -> validate -> preview
  +-- Publish versi immutable
  +-- Activate untuk sensor atau data logger
  |
  v
PILIH JALUR
  |
  +-- BELUM ADA DEVICE
  |     |
  |     +-- Kirim JSON deterministik ke callback HTTP nyata
  |     +-- Uji sukses, zero, missing, duplicate, dan conflict
  |     +-- Periksa raw event, canonical value, trace, rollout evidence
  |     +-- Tetap shadow/non-cutover sampai gate lengkap
  |
  +-- SUDAH ADA DEVICE
        |
        +-- Pilih Modbus RTU/RedNode, Modbus TCP, MQTT, atau HTTP
        +-- Konfigurasi serial/network dan sensor
        +-- Jalankan test port/register
        +-- Jalankan gateway
        +-- Device -> raw ledger -> canonical -> compatibility projection
        +-- Shadow -> attestation -> verified -> cutover
        +-- Monitor, rollback jika mismatch
```

## Bagian A — Menjalankan aplikasi dari awal

### 1. Prasyarat

Gunakan:

- PHP 8.2, 8.3, atau 8.4. `composer.lock` saat ini tidak kompatibel dengan PHP 8.5.
- Composer 2.x.
- Node.js 20 atau lebih baru; `serialport` mensyaratkan Node 20+.
- npm.
- MySQL atau database kompatibel untuk penggunaan normal.
- Akses tulis Laravel ke `storage/` dan `bootstrap/cache/`.

Untuk device RedNode/Bliiot diperlukan tambahan:

- akses serial `/dev/ttyAS*`;
- wiring RS485 yang benar;
- network dari device ke URL Laravel;
- Node.js 20+ di device;
- akses broker bila MQTT digunakan.

### 2. Ambil source dan install dependency

```bash
git clone <URL_REPOSITORY> eas-app
cd eas-app
composer install
npm ci
cd rednode-gateway
npm ci
cd ..
```

Jika host hanya memiliki PHP 8.5, jangan memaksa `composer install` dengan mengabaikan platform requirement. Gunakan runtime PHP 8.2-8.4 terlebih dahulu.

### 3. Buat konfigurasi Laravel

```bash
cp .env.example .env
php artisan key:generate
```

Isi minimal berikut di `.env` dengan nilai environment sendiri:

```dotenv
APP_NAME=RESQ
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<NAMA_DATABASE>
DB_USERNAME=<USER_DATABASE>
DB_PASSWORD=<PASSWORD_DARI_SECRET_MANAGER>

QUEUE_CONNECTION=sync
CANONICAL_MAX_RAW_PAYLOAD_BYTES=1048576
```

Jangan commit `.env`, token, password, cookie, private key, atau connection URI yang berisi credential.

### 4. Siapkan database dan catalog canonical

Untuk database development kosong:

```bash
php artisan migrate --force
php artisan db:seed --class=CanonicalCatalogSeeder --force
```

Pemeriksaan source yang tersedia:

```bash
php artisan canonical:verify-core --seed
php artisan canonical:verify-mapping-workbench
php artisan canonical:verify-live-path
php artisan canonical:verify-replay
php artisan canonical:verify-ingress-convergence
```

Command verifier menggunakan fixture terisolasi/rollback sesuai implementasinya. Ia berguna sebagai feedback development, tetapi tidak menggantikan rehearsal MySQL production-like atau canary device Phase 7.

### 5. Jalankan aplikasi

Terminal 1:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Terminal 2 untuk frontend development:

```bash
npm run dev
```

Untuk build asset:

```bash
npm run build
```

Buka `http://127.0.0.1:8000`, register/login melalui route autentikasi Laravel, lalu gunakan menu authenticated. Hak `manage-canonical-mappings` diperlukan untuk Mapping Workbench, Canonical Trace/Replay, dan Canonical Ingress Rollout.

## Bagian B — Membuat struktur proyek

### 1. Urutan konfigurasi

Buka `/projects` dan buat data dalam urutan berikut:

```text
Project
  -> Workspace
     -> Monitoring Station
        -> MST Prefix
        -> Sensor
        -> Data Logger
           -> Connectivity
           -> Device Credential
```

Urutan tersebut penting karena sensor dan data logger harus berada pada monitoring station yang sama agar callback dapat diterima.

### 2. Project, workspace, dan monitoring station

Di `/projects`:

1. Buat Project dengan `project_code`, nama, owner, tanggal, dan status.
2. Buat Workspace yang menunjuk project, lalu isi kode, nama, provinsi, kota, hazard, dan koordinat bila tersedia.
3. Buat Monitoring Station yang menunjuk workspace, lalu isi `station_code`, nama, status logger, status connectivity, dan status station.
4. Warning Station dan Response Plan dapat ditambahkan bila proyek membutuhkan jalur peringatan.

Route penyimpanan berada di `routes/web.php`: `projects.store`, `project-workspaces.store`, `project-monitoring-stations.store`, `project-warning-stations.store`, `project-sensors.store`, dan `project-response-plans.store`.

### 3. MST prefix dan sensor

1. Buka `/mst-prefixes` dan simpan prefix sensor.
2. Kembali ke `/projects` untuk membuat sensor.
3. Pilih workspace, monitoring station, prefix, dan isi minimal:
   - `sensor_code` yang unik secara operasional;
   - tipe sensor;
   - parameter sumber;
   - `slave_id`;
   - address register;
   - function code `FC01`, `FC02`, `FC03`, atau `FC04`;
   - quantity 1-125;
   - poll interval 250-60000 ms;
   - data type, scale, offset, unit, threshold, dan rule sesuai register map.

Nilai scale/offset pada konfigurasi sensor adalah metadata device/compatibility. Nilai canonical tetap harus mengikuti rule mapping yang disetujui; hindari scaling dua kali.

### 4. Data logger, connectivity, dan credential

Buka `/data-loggers`:

1. Buat data logger dan tautkan ke monitoring station sensor.
2. Gunakan status `Active` agar autentikasi device diterima.

Buka `/connectivity`:

1. Pilih data logger.
2. Isi communication type dan protocol sesuai jalur:

| Kondisi | Communication/protocol yang dikenali | Ingress path |
|---|---|---|
| Simulasi/API HTTP | mengandung `HTTP` atau `API` | `http_callback` |
| MQTT | mengandung `MQTT` | `mqtt` |
| Modbus TCP | mengandung `Modbus TCP` | `modbus_tcp` |
| RS485/RedNode | mengandung `Modbus RTU`, `RS485`, atau memiliki serial port | `rednode_callback` |

Buka `/credentials`:

1. Pilih data logger yang sama.
2. Buat `credential_code`.
3. Isi device token dari secret generator/operator.
4. Gunakan `credential_status=Active` dan jangan isi `revoked_at`.

`DeviceIngressAuthenticator` membandingkan Bearer token dengan credential aktif, memastikan logger aktif, memastikan logger mempunyai monitoring station, dan menolak sensor yang berada di luar station logger.

## Bagian C — Menyiapkan mapping canonical

### 1. Periksa catalog

Buka `/canonical-catalog`. Pastikan parameter, definition version, unit, precision, rounding, dan konversi yang diperlukan tersedia. Catalog default harus mendapat persetujuan domain owner sebelum rollout produksi.

### 2. Buat mapping profile

Buka `/mapping-workbench`:

1. Buat profile dengan nama, manufacturer, device model, dan description.
2. Sistem membuat versi draft.
3. Tambahkan rule yang menghubungkan parameter/item sumber ke canonical parameter version.
4. Tentukan parser, byte/word order, signedness, register range, scale, offset, source unit, missing markers, dan origin `RDM` atau `RDP`.
5. Jalankan Validate.
6. Jalankan Preview menggunakan sample text/hex atau raw item yang sudah tersimpan.
7. Perbaiki semua error.
8. Publish dengan change reason. Versi published menjadi immutable.
9. Activate ke source `sensor:<ID>` atau `data_logger:<ID>` dengan activation reason.

Urutan teksnya:

```text
Draft profile
  -> rule lengkap
  -> validate
  -> preview
  -> publish immutable version
  -> activate assignment
  -> event berikutnya memakai version tersebut
```

Jangan mengedit versi published. Clone ke draft baru, publish, lalu activate/rollback assignment bila semantics berubah.

## Bagian D — Cabang 1: simulasi ketika belum ada device

### 1. Apa yang disimulasikan

Simulasi ini mengirim envelope device ke callback Laravel yang sama dengan device nyata:

```text
curl/HTTP client
  -> POST /api/realtime-sensor-status
  -> Bearer credential
  -> DeviceIngressAuthenticator
  -> exact raw payload + hash
  -> mapping aktif
  -> deterministic transformer
  -> canonical history/current-head decision
  -> rollout evidence
  -> response dengan raw_event_id dan canonical run
```

Ini bukan simulator CommonJS penuh Phase 7. Ia cocok untuk smoke/integration check saat hardware belum ada.

### 2. Persiapan simulasi

Pastikan:

1. aplikasi dan database berjalan;
2. sensor, logger, connectivity HTTP/API, dan credential aktif sudah dibuat;
3. sensor dan logger berada pada monitoring station yang sama;
4. mapping published sudah aktif untuk sensor/logger;
5. path `http_callback` minimal berada pada `expand` atau `shadow`;
6. token hanya disimpan di environment shell lokal.

```bash
export SIM_BASE_URL='http://127.0.0.1:8000'
export SIM_CALLBACK_TOKEN='<DEVICE_TOKEN_AKTIF>'
export SIM_SENSOR_CODE='<SENSOR_CODE>'
export SIM_EVENT_ID='sim-http-001'
```

### 3. Kirim event register mentah

Simpan payload agar retry menggunakan byte JSON yang sama:

```bash
cat > /tmp/resq-sim-event.json <<JSON
{
  "event_id": "${SIM_EVENT_ID}",
  "envelope_version": "1",
  "sensor_code": "${SIM_SENSOR_CODE}",
  "transport": "http",
  "payload_classification": "raw",
  "observed_at": "2026-07-31T12:00:00Z",
  "registers": [302],
  "register_address": 0,
  "function_code": "FC03"
}
JSON

curl --fail-with-body \
  -X POST "${SIM_BASE_URL}/api/realtime-sensor-status" \
  -H 'Content-Type: application/json' \
  -H "Authorization: Bearer ${SIM_CALLBACK_TOKEN}" \
  --data-binary @/tmp/resq-sim-event.json
```

Response sukses mempunyai bentuk utama:

```json
{
  "ok": true,
  "raw_event_id": 123,
  "event_id": "sim-http-001",
  "idempotent": false,
  "canonical": {
    "run_id": 45,
    "status": "completed",
    "mapped": true,
    "value_ids": [67],
    "projected_value_id": null
  }
}
```

ID dan nilai di atas hanya contoh bentuk, bukan expected result. `projected_value_id` dapat `null` saat path belum cutover atau outcome tidak eligible.

### 4. Periksa raw dan canonical

Ambil `raw_event_id` dari response:

```bash
php artisan canonical:raw:inspect <RAW_EVENT_ID> --hex-preview
```

Kemudian periksa:

- `/canonical-trace/raw/<RAW_EVENT_ID>` untuk lineage;
- `/telemetry` dan `/telemetry/live-data` untuk consumer;
- `/canonical-ingress-rollout?path=http_callback` untuk evidence/parity;
- tabel `raw_ingestion_events`, `raw_ingestion_items`, `canonical_processing_runs`, `canonical_values`, dan `canonical_current_heads` bila melakukan pemeriksaan database langsung.

Raw event harus tetap ada walaupun mapping gagal. Missing tidak boleh berubah menjadi zero. Canonical history tidak boleh ditimpa oleh event baru.

### 5. Skenario minimum tanpa device

Gunakan event ID berbeda kecuali pada uji duplicate/conflict.

| Skenario | Input | Expected semantic |
|---|---|---|
| sukses | register/value valid sesuai mapping | raw tersimpan; canonical mapped bila assignment cocok |
| zero | register `0` | nilai zero, bukan missing |
| missing | hilangkan `registers` dan `value` | raw item berstatus missing; current head tidak menjadi zero |
| malformed | JSON rusak | HTTP 422 dan raw rejection evidence bila source sudah terautentikasi |
| unauthorized | token kosong/salah | HTTP 401/403; jangan retry tanpa memperbaiki credential |
| duplicate | kirim ulang file JSON yang sama | `idempotent=true`; tidak menambah canonical result |
| conflicting duplicate | event ID sama tetapi register berbeda | HTTP 409; evidence pertama tetap dipertahankan |
| late | timestamp lebih lama dari current head | history tersimpan tetapi head/projection tidak mundur |

Untuk duplicate, ulangi command curl tanpa mengubah `/tmp/resq-sim-event.json`. Untuk conflict, salin file, ubah register tetapi pertahankan `event_id`; expected HTTP 409.

### 6. Verifier tambahan

```bash
php artisan canonical:verify-live-path
php artisan canonical:verify-ingress-convergence --path=http_callback --assert-operator-http --assert-consumer-cutover
```

Verifier tersebut memberi bukti development yang repeatable. Compatible MySQL, concurrency/volume, queue, build, operator kedua, dan real-device canary tetap mengikuti `docs/PHASE-7-SIMULATOR-ROLLOUT-GATE.md`.

## Bagian E — Cabang 2: ketika device sudah tersedia

### 1. Tentukan jenis koneksi

```text
Device RS485/Modbus RTU
  -> rednode-gateway/gateway.js
  -> POST /api/realtime-sensor-status

Device Modbus TCP
  -> modbus-server/server.js
  -> callback Laravel

Device MQTT
  -> broker
  -> modbus-server/server.js MQTT subscriber
  -> callback Laravel

Device HTTP native
  -> POST /api/realtime-sensor-status langsung
```

Untuk semua jalur, raw evidence disimpan dahulu. Gateway tidak boleh menjadi pemilik semantics canonical; mapping dan transformasi canonical tetap dilakukan Laravel.

### 2. Flow RedNode/Bliiot Modbus RTU

Di web `/modbus-configuration`:

1. Pilih data logger dan sensor yang akan dimonitor.
2. Isi logger code yang sama dengan credential.
3. Isi serial port, baud rate, data bits, stop bits, parity, timeout, pin mapping, dan poll interval.
4. Simpan konfigurasi.
5. Isi remote host/SSH hanya bila fitur kontrol remote akan dipakai.

Mapping BL118 yang terdokumentasi di repository:

```text
PIN 1-2 -> /dev/ttyAS4
PIN 3-4 -> /dev/ttyAS5
PIN 5-6 -> /dev/ttyAS2
PIN 7-8 -> /dev/ttyAS3
```

Di device, dari folder `rednode-gateway`:

```bash
npm ci
npm run test-pins
npm run test-ports
```

`test-ports` menghasilkan JSON diagnostik. Pastikan register yang dibaca cocok dengan register map vendor sebelum menjalankan gateway terus-menerus.

### 3. Environment RedNode

Contoh tanpa credential nyata:

```dotenv
APP_URL=http://<HOST_LARAVEL>:8000
REDNODE_CONFIG_URL=http://<HOST_LARAVEL>:8000/api/rednode/config
REDNODE_CALLBACK_URL=http://<HOST_LARAVEL>:8000/api/realtime-sensor-status
REDNODE_HEARTBEAT_URL=http://<HOST_LARAVEL>:8000/api/rednode/heartbeat
REDNODE_CONFIG_TOKEN=<TOKEN_SOURCE_BOUND>
REDNODE_CALLBACK_TOKEN=<TOKEN_SOURCE_BOUND>
REDNODE_LOGGER_CODE=<LOGGER_CODE_AKTIF>
REDNODE_SERIAL_PORT=/dev/ttyAS2
REDNODE_BAUD_RATE=9600
REDNODE_DATA_BITS=8
REDNODE_STOP_BITS=1
REDNODE_PARITY=none
REDNODE_TIMEOUT_MS=1500
REDNODE_CONFIG_REFRESH_MS=5000
REDNODE_POLL_INTERVAL_MS=1000
REDNODE_MQTT_ENABLED=false
```

Token config/callback harus cocok dengan active `DeviceCredential` logger atau legacy source-bound configuration. Jangan menaruh token dalam log atau dokumentasi evidence.

### 4. Uji config sebelum start

```bash
curl --fail-with-body \
  -H "Authorization: Bearer ${REDNODE_CONFIG_TOKEN}" \
  "${REDNODE_CONFIG_URL}?logger_code=${REDNODE_LOGGER_CODE}"
```

Response harus memuat logger, serial configuration, callback URL, heartbeat URL, dan daftar sensor yang sesuai station/logger. Jika daftar sensor kosong, periksa station binding, slave ID, address, dan monitored sensor selection.

### 5. Jalankan gateway

Dari folder `rednode-gateway`:

```bash
npm run gateway
```

Atau dari root repository:

```bash
npm run rednode:gateway
```

Flow runtime:

```text
gateway fetch config
  -> buka serial port
  -> poll sensor berdasarkan interval
  -> capture registers/raw
  -> POST callback dengan stable event_id
  -> Laravel autentikasi source
  -> raw ledger
  -> mapping + canonical processing
  -> rollout evidence
  -> optional compatibility projection
  -> heartbeat melaporkan connectivity saja
```

Log gateway menggunakan prefix `[config]`, `[serial]`, `[sensor]`, `[callback]`, dan `[mqtt]`. Error per sensor tidak boleh langsung dianggap kegagalan seluruh proses; periksa state dan retry.

### 6. Flow Modbus TCP lokal

Jalankan service:

```bash
npm run modbus:server
```

Default port HTTP control plane adalah 3100. Endpoint yang tersedia pada `modbus-server/server.js` meliputi:

```text
GET  /health
POST /api/modbus/connect
POST /api/modbus/disconnect
POST /api/modbus/read
POST /api/modbus/write
GET  /api/poll/status
POST /api/poll/start
POST /api/poll/stop
```

Gunakan halaman `/modbus-configuration` sebagai UI control. Service ini membutuhkan endpoint Modbus TCP yang benar-benar dapat dijangkau; repository tidak menyertakan server/device emulator Modbus TCP.

### 7. Flow MQTT

Isi runtime environment broker dan callback, lalu:

```bash
npm run mqtt:gateway
```

Environment utama:

```dotenv
MQTT_BROKER_URL=mqtts://<BROKER>:8883
MQTT_TOPIC=resq/telemetry/#
MQTT_USERNAME=<USER>
MQTT_PASSWORD=<SECRET>
MQTT_CALLBACK_URL=http://<HOST_LARAVEL>:8000/api/realtime-sensor-status
MQTT_CALLBACK_TOKEN=<TOKEN_SOURCE_BOUND>
```

Payload broker mempertahankan message sebagai evidence, sedangkan value-oriented candidate diperlakukan sebagai `pre_normalized`. Laravel tetap melakukan conversion/rounding canonical yang diizinkan.

## Bagian F — Rollout dari evidence ke cutover

Jangan langsung cutover setelah menerima HTTP 200.

```text
expand
  -> shadow
     -> current-suite attestation PASS
        -> verified
           -> cutover

shadow/verified/cutover
  -> rolled_back
     -> shadow untuk mencoba lagi
```

### Langkah operator

1. Buka `/canonical-ingress-rollout`.
2. Pilih path: `http_callback`, `modbus_tcp`, `mqtt`, `rednode_callback`, `rednode_heartbeat`, atau `manual`.
3. Dari `expand`, transition ke `shadow` dengan reason yang dapat diaudit.
4. Jalankan traffic simulasi/device dan periksa accepted, idempotent, rejected, conflict, mapped, canonical, projection, serta parity.
5. Jalankan verifier path dan, bila memang akan membuat attestation, gunakan verified user ID:

```bash
php artisan canonical:verify-ingress-convergence \
  --path=rednode_callback \
  --attest \
  --actor=<VERIFIED_USER_ID>
```

6. Pastikan attestation current-suite mempunyai zero failure dan masih berada dalam freshness window 24 jam.
7. Transition `shadow -> verified`.
8. Lakukan canary dan direct-database checks.
9. Transition `verified -> cutover` hanya jika seluruh gate target lulus.

Hanya state `cutover` yang mengaktifkan canonical current read. State lain tetap memilih legacy compatibility behavior.

## Bagian G — Cara memastikan aliran berhasil

### Checklist aplikasi

- callback mengembalikan `ok=true`;
- `raw_event_id` tersedia;
- event ID retry menghasilkan `idempotent=true`;
- conflicting duplicate menghasilkan HTTP 409;
- raw payload hash dan ukuran tersimpan;
- mapping/profile version yang dipakai benar;
- canonical run mempunyai status yang diharapkan;
- typed canonical value berada pada kolom sesuai data type;
- current head hanya maju menurut ordering;
- missing/late/non-value tidak mengganti current head;
- legacy telemetry hanya diperbarui oleh winner eligible sesuai rollout state;
- trace dapat kembali ke raw event, item, mapping version/rule, run, dan value.

### Checklist device

- device dan firmware sesuai register map;
- port serial dan wiring benar;
- slave ID, function code, address, quantity, data type, byte order, word order benar;
- exact raw register/packet disimpan sebelum transformasi;
- expected value dihitung independen dari production transformer;
- gate mendeteksi endian salah dan double-scaling;
- heartbeat hanya mengubah connectivity, bukan menciptakan telemetry palsu;
- reconnect/retry mempertahankan event identity.

### Status halaman

| Halaman | Fungsi pemeriksaan |
|---|---|
| `/telemetry` | latest telemetry/consumer view |
| `/telemetry/live-data` | polling current data |
| `/canonical-trace` | raw-to-canonical lineage dan replay |
| `/canonical-ingress-rollout` | path state, evidence, parity, transition history |
| `/modbus-configuration` | connectivity, polling, RedNode serial/control |
| `/rednode-status` | runtime status RedNode |
| `/dashboard` | kondisi aplikasi dan peta |

## Bagian H — Kondisi gagal dan tindakan

| Gejala | Kemungkinan sebab | Tindakan |
|---|---|---|
| HTTP 401 | token kosong/tidak cocok | periksa active credential; jangan cetak token |
| HTTP 403 | transport tidak diizinkan atau logger scope salah | periksa connectivity protocol dan logger binding |
| HTTP 422 sensor tidak ditemukan | sensor code/ID salah atau beda station | samakan sensor dan logger pada monitoring station |
| HTTP 422 envelope invalid | event ID, transport, timestamp, atau register invalid | cocokkan payload dengan validation controller |
| HTTP 409 | event ID sama dengan payload berbeda | gunakan event ID baru; jangan hapus evidence pertama |
| `canonical.mapped=false` | tidak ada published active assignment/rule cocok | validate, publish, dan activate mapping |
| canonical ada tetapi projection null | belum cutover, late/non-value, atau bukan winner | periksa rollout state dan head ordering |
| config RedNode kosong | logger/sensor/serial selection tidak cocok | periksa station, monitored sensor IDs, address/slave ID |
| serial open gagal | port, permission, wiring, atau serial setting salah | jalankan pin/port test dan cek permission device |
| nilai kelipatan scale | double-scaling | bandingkan raw register dengan independent oracle; rollback |
| nilai sangat berbeda | endian/word order salah | hentikan cutover dan perbaiki draft mapping |
| heartbeat ada tetapi telemetry tidak ada | serial/poll/callback sensor gagal | periksa `[serial]`, `[sensor]`, dan `[callback]` logs |

## Bagian I — Rollback aman

Jika mismatch ditemukan:

1. hentikan simulator/gateway yang menghasilkan data salah;
2. transition path ke `rolled_back` melalui UI ber-auth, Gate, CSRF, dan session actor;
3. pastikan canonical read disabled dan consumer kembali ke legacy fallback;
4. jangan hapus raw events, canonical values, rollout evidence, atau defect events;
5. clone mapping published ke draft baru;
6. perbaiki rule, validate, preview, publish, dan activate versi baru;
7. ulangi dari `rolled_back -> shadow -> verified -> cutover`;
8. simpan actor, reason, UTC, command, event IDs, expected/actual, dan artifact checksum.

## Ringkasan keputusan

```text
Jika belum ada device:
  gunakan callback HTTP nyata + credential source-bound
  -> periksa raw/canonical/trace/evidence
  -> jangan klaim real-device gate lulus

Jika device sudah ada:
  validasi register map + port + exact raw capture
  -> jalankan gateway
  -> shadow evidence
  -> independent expected calculation
  -> canary endian dan double-scaling
  -> attestation
  -> verified/cutover atau rollback

Jika ada FAIL, BLOCKED, atau NOT RUN pada gate wajib:
  keputusan adalah NO-GO untuk scope tersebut.
```

Untuk release gate Phase 7 yang lebih rinci, gunakan `docs/PHASE-7-SIMULATOR-ROLLOUT-GATE.md`. Untuk penjelasan internal implementasi Phase 1-6, gunakan `docs/IMPLEMENTASI-CANONICAL-DATA-TERKINI.md`.
