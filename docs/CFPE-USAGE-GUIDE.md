# CFPE & Geospatial Workspace
## Prasyarat
- Project sudah terdaftar di sistem (misal: **Sentinal Project**)
- Geospatial Workspace sudah dibuat (misal: **Semeru Geospatial Workspace**)
- File training data tersedia di `public/training_data/`
---
## Langkah 1: Import Monitoring Corridor (GPKG)
1. Buka **CFPE Corridor** di sidebar
2. Expand panel **"GPKG Import (Corridor & Information Layer)"**
3. Setting:
   - Import Type: **Monitoring Corridor**
   - Project: **Sentinal Project**
   - Workspace: **Semeru Geospatial Workspace**
4. Pilih satu atau beberapa file GPKG dari `public/training_data/Monitoring_Corridor/`:
   - `Koridor_Kali_Lengkong.gpkg`
   - `Koridor_Kali_Glidik.gpkg`
   - `Koridor_Kali_Leprak.gpkg`
   - `Koridor_Kali_Mujur.gpkg`
   - `Confluence.gpkg`
5. Klik **Import GPKG**
**Hasil:** Polyline corridor (jalur sungai asli) muncul di peta.
---
## Langkah 2: Import Information Layer (GPKG)
1. Masih di panel GPKG Import
2. Setting:
   - Import Type: **Information Layer**
   - Project: **Sentinal Project**
   - Workspace: **Semeru Geospatial Workspace** (opsional)
3. Pilih satu atau beberapa file GPKG dari `public/training_data/Information_Layer/`:
   - `IL-Sungai.gpkg` — jaringan sungai
   - `IL-Batas_Desa.gpkg` — batas administrasi desa
   - `IL-Permukiman.gpkg` — area permukiman
   - `IL-Pendidikan.gpkg` — fasilitas pendidikan
   - `IL-Kesehatan.gpkg` — fasilitas kesehatan
   - `IL-Perimeter_Semeru_(5km).gpkg` — radius bahaya
   - `IL-Puncak_Semeru.gpkg` — titik puncak
   - `IL-Contour.gpkg` — garis kontur
4. Klik **Import GPKG**
**Hasil:** Layer informasi (polygon, garis) muncul di peta sebagai konteks area.
---
## Langkah 3: Import Route CSV (Titik BM untuk Kalkulasi CFPE)
1. Expand panel **"CSV Route Import"**
2. Setting:
   - Project: **Sentinal Project**
   - Workspace: **Semeru Geospatial Workspace**
3. Pilih satu atau beberapa file CSV dari `public/training_data/`:
   - `Route_KKL_JSH01.csv`
   - `Route_KKL_JSH02.csv`
   - `Route_KKL_JSH03.csv`
   - `Route_KKL_JSH04.csv`
4. Klik **Import CSV**
**Hasil:** Titik-titik BM (Benchmark) muncul di peta + route tersedia untuk perhitungan CFPE.
---
## Langkah 4: Hitung CFPE (ETA Propagation)
1. Di panel **CFPE Configuration** (kiri):
   - Project: **Sentinal Project**
   - Route: pilih salah satu (misal **KKL-JSH01**)
   - Reference BM: pilih titik ground zero (misal **KKL-JSH01-02** di chainage 2000m)
   - Offset Direction: **Downstream**
   - Offset Distance: **150** (meter)
   - Flow Velocity: **2.0** (m/s) — kecepatan aliran
   - Uncertainty Factor: **0.30** (±30%)
2. Klik **Calculate**
**Hasil:** Tabel ETA muncul, menunjukkan untuk setiap BM di hilir:
| Reference Point | Chainage | Distance | Basic ETA | ETA Range |
|---|---|---|---|---|
| KKL-JSH01-03 | 3,150 m | 1,000 m | 8.3 min | 6 – 11 min |
| KKL-JSH01-04 | 4,150 m | 2,000 m | 16.7 min | 12 – 22 min |
| KKL-JSH01-05 | 5,150 m | 3,000 m | 25.0 min | 18 – 33 min |
**Cara Baca:** Jika aliran terdeteksi di Ground Zero (chainage 2,150m) dengan kecepatan 2 m/s, banjir akan sampai di BM-03 dalam **6-11 menit**, di BM-04 dalam **12-22 menit**.
---
## Urutan Import yang Benar
```
1. Import Corridor (GPKG)       → visualisasi jalur sungai di peta
2. Import Information Layer (GPKG) → konteks area (permukiman, batas desa, dll)
3. Import Route CSV             → titik BM dengan chainage untuk kalkulasi
4. Calculate CFPE               → estimasi waktu tiba banjir/lahar
```
---
## Rumus CFPE
```
Station Ground Zero = BM Chainage + Offset Distance (downstream)
Propagation Distance = Target BM Chainage - Station Ground Zero
Basic ETA (detik) = Propagation Distance / Flow Velocity
Basic ETA (menit) = Basic ETA (detik) / 60
ETA Min = Basic ETA × (1 - Uncertainty Factor)
ETA Max = Basic ETA × (1 + Uncertainty Factor)
```
---
## Catatan
- Semua file GPKG menggunakan CRS **EPSG:4326** (WGS84)
- Map extent default: West 112.783, East 113.249, South -8.371, North -8.056
- Route CSV menggunakan delimiter **semicolon (;)**
- Chainage dalam CSV menggunakan format koma ribuan (misal: `1,000.00`)
- Import multi-file: pilih beberapa file sekaligus, sistem akan memproses satu per satu
