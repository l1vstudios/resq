# Rika Sensors Modbus Profile Documentation

## Prinsip Dasar

Gateway **tidak boleh** menebak arti register Modbus mentah menjadi parameter semantik (`wind_speed`, `wind_direction`, dll). Semua pemetaan harus:

1. **Eksplisit** — Diturunkan dari datasheet resmi sensor
2. **Verifiable** — Dapat dibuktikan dengan spesifikasi teknis
3. **Configurable** — Dapat diubah per model sensor tanpa mengubah kode

---

## Cara Mendapatkan Spesifikasi Sensor Rika

### 1. Sumber Dokumentasi Resmi

- **Rika Sensors Official Website**: [https://www.rikasensor.com/](https://www.rikasensor.com/)
- **Product Datasheets**: Tersedia di halaman produk masing-masing model
- **Modbus Register Map**: Biasanya ada di lampiran datasheet atau dokumen terpisah "Communication Protocol"

### 2. Informasi yang Diperlukan dari Datasheet

Untuk setiap model sensor, catat:

| Item | Contoh |
|------|--------|
| Model Number | RK900-01, RK120-03, RK500-04, dll |
| Slave ID Default | 1 (biasanya dapat diubah) |
| Baud Rate | 4800, 9600, 19200 |
| Data Format | 8N1, 8E1 |
| Function Code | 0x03 (Read Holding Registers), 0x04 (Read Input Registers) |
| Register Address | Alamat awal register data |
| Register Count | Jumlah register yang dibaca |
| Data Type per Register | INT16, UINT16, Float32, dll |
| Scale Factor | Faktor pengali (misal: ×0.1 untuk resolusi 0.1°C) |
| Parameter Order | Urutan parameter di register berturutan |
| Value Range | Rentang nilai valid |
| Unit | Satuan pengukuran |

---

## Template Profile Sensor

Buat profile untuk setiap model sensor di database (`sensors.weather_parameters`) atau file JSON:

```json
{
  "model": "RK900-01",
  "vendor": "Rika Sensors",
  "description": "Compact Weather Station",
  "modbus": {
    "default_slave_id": 1,
    "default_baud_rate": 9600,
    "data_format": "8N1",
    "function_code": "0x03",
    "start_address": 0,
    "register_count": 8
  },
  "parameters": [
    {
      "index": 0,
      "name": "wind_speed",
      "label": "Kecepatan Angin",
      "data_type": "uint16",
      "scale_factor": 0.1,
      "offset": 0,
      "unit": "m/s",
      "range": { "min": 0, "max": 60 },
      "datasheet_reference": "RK900-01 Datasheet v2.3, Page 12, Table 5"
    },
    {
      "index": 1,
      "name": "wind_direction",
      "label": "Arah Angin",
      "data_type": "uint16",
      "scale_factor": 1,
      "offset": 0,
      "unit": "°",
      "range": { "min": 0, "max": 360 },
      "datasheet_reference": "RK900-01 Datasheet v2.3, Page 12, Table 5"
    }
  ],
  "notes": "Urutan parameter wajib sesuai datasheet. JANGAN tebak dari nama atau posisi register."
}
```

---

## Validasi Nilai Berdasarkan Range

Setiap parameter memiliki range fisik yang wajar:

| Parameter | Range Minimum | Range Maximum | Catatan |
|-----------|---------------|---------------|---------|
| `wind_speed` | 0 m/s | 60 m/s | Di atas ini = badai ekstrem / noise |
| `wind_direction` | 0° | 360° | 0° = Utara, 90° = Timur |
| `temperature` | -40°C | 80°C | Tergantung sensor model |
| `humidity` | 0% | 100% | Relatif |
| `pressure` | 300 hPa | 1100 hPa | Tekanan atmosfer |
| `rainfall` | 0 mm | 999 mm | Kumulatif atau per jam |
| `solar_radiation` | 0 W/m² | 1500 W/m² | Intensitas matahari |
| `battery_voltage` | 0 V | 24 V | Tergantung sistem |

**Jika nilai di luar range ini tanpa konfigurasi eksplisit, gateway harus menandai sebagai "unmapped" atau "raw only".**

---

## Implementasi di Sistem

### 1. Database: master parameter + mapping profile

Gunakan `canonical_parameters.input_requirements` untuk menyimpan syarat datasheet per parameter:

- `min_value` / `max_value`
- `resolution`
- `accuracy`
- `source_url`
- `source_reference`
- `source_note`

Gunakan `sensor_mapping_profiles` untuk menghubungkan sensor fisik ke parameter canonical. Satu sensor multiparameter boleh punya banyak profile aktif, misalnya satu profile untuk `wind_speed`, satu untuk `wind_direction`, dan seterusnya. Setiap profile wajib menyimpan `register_address`, `source_parameter`, `source_unit`, `value_type`, `data_length`, `scale_factor`, dan `offset` sesuai manual/datasheet.

Kolom `sensors.weather_parameters` hanya kompatibilitas lama untuk weather station yang sudah terlanjur memakai daftar eksplisit:

```php
// Contoh: Model RK900-01
$sensor->weather_parameters = [
    'wind_speed',      // Register index 0
    'wind_direction',  // Register index 1
    'temperature',     // Register index 2
    'humidity',        // Register index 3
    'pressure',        // Register index 4
    'rainfall',        // Register index 5
];
```

### 2. Gateway Behavior

```javascript
// Jika mapped_parameters dan weather_parameters kosong:
// - Gateway menyimpan register mentah saja
// - Tidak ada interpretasi wind_speed / wind_direction

// Jika mapped_parameters ada:
// - Gateway memetakan register berdasarkan register_index/register_address dari mapping profile
// - Label, unit, scale_factor, offset, range, resolution, accuracy, dan datasheet source berasal dari database
// - Berlaku untuk semua sensor multiparameter, bukan hanya weather station

// Jika hanya weather_parameters lama yang ada:
// - Gateway tetap memakai daftar eksplisit itu untuk kompatibilitas
// - Gateway tidak membuat default parameter sendiri
```

### 3. Admin UI

Sediakan input master parameter dan mapping profile berdasarkan datasheet. Untuk RK900-11, sumber spesifikasi utama adalah tabel **SPECIFICATIONS** pada halaman produk resmi Rika Sensor.

---

## Catatan Penting

1. **JANGAN** hardcode urutan parameter di gateway
2. **SELALU** simpan `registers` (raw array) untuk audit
3. **WAJIB** isi mapping profile aktif untuk setiap parameter yang ingin dibaca dari sensor multiparameter
4. **REFERENSIKAN** datasheet saat mengisi konfigurasi

---

## Referensi Datasheet Rika Sensors

Isi link dan versi datasheet yang digunakan:

| Model | Datasheet Link | Versi | Tanggal Akses |
|-------|----------------|-------|---------------|
| RK900-01 | (isi link) | (isi versi) | (isi tanggal) |
| RK900-11 | [Link](https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html) | Technical Spec Table | 2026-08-10 |
| RK120-03 | (isi link) | (isi versi) | (isi tanggal) |
| RK500-04 | (isi link) | (isi versi) | (isi tanggal) |

---

## Profil Terverifikasi RK900-11 (Spesifikasi Fisik & Keamanan Modbus)

Spesifikasi fisik resmi dari RK900-11 Ultrasonic Automatic Weather Instrument telah terverifikasi sebagai berikut:

| Parameter | Rentang Fisik | Resolusi | Akurasi | Catatan |
|-----------|---------------|----------|---------|---------|
| `wind_speed` | 0 ~ 40 m/s | 0.1 m/s | ±5% | |
| `wind_direction` | 0 ~ 359° | 1° | ±3° | |
| `temperature` | -40℃ ~ +80℃ | 0.1℃ | ±1℃ | |
| `humidity` | 0 ~ 100% | 1% | ±3% | |
| `pressure` | 300 ~ 1100 hPa | 0.1 hPa | ±2 hPa | |
| `rainfall_rate` | 0 ~ 200 mm/hr | 0.1 mm | ±8% | Kecepatan angin ≤ 5m/s |
| `altitude` | -500m ~ 9000m | 1m | ±8% | |
| `irradiance` | 0 ~ 2000 W/m² | 0.1 W/m² | ±5% | Cahaya vertikal |
| `illumination` | 0 ~ 200,000 lux | 0.1 lux | ±5% | Cahaya vertikal |
| `pm25` | 0 ~ 2000 μg/m³ | 1 μg/m³ | ±5% | |
| `pm10` | 0 ~ 2000 μg/m³ | 1 μg/m³ | ±8% | |

### Status Protokol Modbus: **UNVERIFIED**

> **PENTING**: Dokumen spesifikasi produk tidak mencantumkan parameter Modbus (Baud Rate, Slave ID, Data Format, Function Code, Register Map, Data Type, Byte Order/Endianness, dan Scale Factor).
> **DILARANG MENEBAK** atau menyamakan register map ini dengan RK900-01 atau model Rika lainnya tanpa adanya dokumen resmi "Communication Protocol" untuk RK900-11.

#### Tindakan Gateway & Database:
1. **Raw Only**: Gateway harus menyimpan data mentah (`registers`) ke database tanpa melakukan konversi ke field parameter (`wind_speed`, `wind_direction`, dll) hingga konfigurasi register dikonfirmasi resmi.
2. **Pencocokan Rentang (Range Validation)**: Jika suatu saat konfigurasi register dipetakan, batas fisik di atas harus dijadikan rujukan validitas nilai. Setiap nilai di luar rentang di atas harus diabaikan atau ditandai error.

**Tabel ini harus diisi oleh tim yang memiliki akses ke datasheet resmi Rika Sensors.**

---

## Logger Bliiot (Node-RED)

Untuk integrasi dengan logger Bliiot:

1. Gateway kompatibel dengan semua serial port Bliiot (ttyAS2, ttyAS3, ttyAS4, ttyAS5)
2. Pin mapping dikonfigurasi di `connectivity_configs.pin_mapping`
3. Tidak ada asumsi khusus untuk Bliiot — semua konfigurasi berbasis database

---

*Dokumen ini dibuat untuk memastikan sistem kompatibel dengan semua sensor Rika tanpa menebak arti register Modbus.*
