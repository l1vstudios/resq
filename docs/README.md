# Pusat Dokumentasi RESQ Early Warning System

Dokumentasi ini disusun dari implementasi aktual di repository per 7 Agustus 2026. Source code tetap menjadi sumber kebenaran terakhir bila dokumentasi dan implementasi berubah tidak bersamaan.

## Jalur belajar yang disarankan

| Urutan | Dokumen | Hasil yang diharapkan |
|---|---|---|
| 1 | [Panduan Lengkap dan Flow Aplikasi](01-PANDUAN-LENGKAP.md) | Bisa memasang aplikasi dan memahami urutan konfigurasi dari project sampai telemetry |
| 2 | [Arsitektur dan Model Data](02-ARSITEKTUR-DAN-DATA.md) | Memahami batas komponen, relasi tabel, serta alur HTTP/MQTT/Modbus |
| 3 | [Panduan Pengembangan dan Referensi API](03-PENGEMBANGAN-DAN-API.md) | Bisa menambah field, fitur, halaman, endpoint, sensor, dan test dengan pola yang konsisten |
| 4 | [Operasional, Keamanan, dan Troubleshooting](04-OPERASIONAL-KEAMANAN-TROUBLESHOOTING.md) | Bisa menjalankan gateway, menyiapkan production, dan mendiagnosis masalah umum |
| 5 | [Tutorial MQTT VPS dan M100](TUTORIAL_MQTT_VPS_M100.txt) | Bisa menyiapkan Mosquitto, M100, dan MQTT gateway secara rinci |

## Peta cepat pertanyaan

- “Harus mulai input data dari menu mana?” — [Flow penggunaan aplikasi](01-PANDUAN-LENGKAP.md#flow-penggunaan-aplikasi-langkah-demi-langkah)
- “Data sensor bergerak dari perangkat ke dashboard bagaimana?” — [Alur telemetry](02-ARSITEKTUR-DAN-DATA.md#alur-telemetry-end-to-end)
- “Apa beda tiga file gateway Node?” — [Batas komponen gateway](02-ARSITEKTUR-DAN-DATA.md#batas-komponen-nodejs)
- “Bagaimana menambah fitur?” — [Workflow perubahan kode](03-PENGEMBANGAN-DAN-API.md#workflow-perubahan-kode-yang-aman)
- “Bagaimana menambah sensor atau tipe sensor?” — [Tutorial fitur sensor](03-PENGEMBANGAN-DAN-API.md#tutorial-menambah-tipe-sensor)
- “Endpoint apa yang tersedia?” — [Referensi API](03-PENGEMBANGAN-DAN-API.md#referensi-api)
- “Kenapa logger tidak online?” — [Troubleshooting](04-OPERASIONAL-KEAMANAN-TROUBLESHOOTING.md#troubleshooting)
- “Apa risiko yang perlu dibereskan?” — [Temuan dan utang teknis](04-OPERASIONAL-KEAMANAN-TROUBLESHOOTING.md#temuan-penting-dan-utang-teknis)

## Istilah yang dipakai

- **Project**: payung implementasi/program.
- **Geospatial Workspace/Cluster**: wilayah/hazard di dalam project.
- **Monitoring Station**: lokasi akuisisi data.
- **Data Logger/RedNode**: komputer/gateway yang membaca sensor.
- **Sensor**: definisi sumber pembacaan, termasuk alamat Modbus dan aturan alert.
- **Warning Station**: lokasi/perangkat keluaran peringatan.
- **Telemetry Reading**: snapshot pembacaan operasional terbaru.
- **Canonical Data**: hasil normalisasi nilai vendor menjadi field dan unit baku.
- **Fresh**: data yang waktu terimanya masih berada dalam jendela freshness.

## Batas dokumentasi

Dokumentasi membedakan tiga hal agar tidak menimbulkan asumsi keliru:

1. **Aktif di route** — benar-benar dapat dipanggil melalui aplikasi saat ini.
2. **Ada di kode tetapi tidak diroute** — implementasi tersisa/alternatif, bukan flow runtime aktif.
3. **Konfigurasi saja** — data sudah dapat disimpan, tetapi automasi tindak lanjutnya belum diimplementasikan.

Contoh penting: `ResponsePlan` dapat disimpan, tetapi pengiriman SMS dan aktivasi warning station otomatis belum terlihat sebagai job/service aktif di repository ini.
