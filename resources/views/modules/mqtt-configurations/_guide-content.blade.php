{{-- SECTION 1: Overview --}}
<div class="mb-4">
    <h5 class="text-primary"><i class="bx bx-info-circle me-1"></i> Apa itu MQTT Configuration?</h5>
    <p>MQTT Configuration menghubungkan sensor/data logger di lapangan dengan server RESQ melalui broker MQTT. Setiap konfigurasi terdiri dari:</p>
    <ul>
        <li><strong>Broker</strong> — Server MQTT perantara (misalnya Mosquitto, EMQX, HiveMQ)</li>
        <li><strong>Consumer</strong> — Subscribe ke topic untuk menerima data dari device</li>
        <li><strong>Producer</strong> — Publish data ke topic untuk dikirim ke device/sistem lain</li>
    </ul>
    <div class="alert alert-success mt-3 mb-0">
        <i class="bx bx-rocket me-1"></i>
        <strong>Plug-and-Play:</strong> Tidak perlu definisi JSON payload manual. RESQ otomatis mengenali format berdasarkan sensor dan mapping preset yang terpasang di logger. Cukup buat konfigurasi broker, aktifkan consumer, dan data langsung masuk.
    </div>
</div>

{{-- SECTION 2: Membuat Baru --}}
<div class="mb-4">
    <h5 class="text-primary"><i class="bx bx-plus-circle me-1"></i> Membuat MQTT Configuration Baru</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th style="width:30%">Field</th>
                    <th>Penjelasan</th>
                    <th style="width:30%">Contoh</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Project</strong></td>
                    <td>Pilih project yang sensor-sensornya akan dihubungkan via MQTT ini.</td>
                    <td>RESQ Padang</td>
                </tr>
                <tr>
                    <td><strong>Configuration Code</strong></td>
                    <td>Kode unik untuk identifikasi konfigurasi. Gunakan format yang konsisten.</td>
                    <td><code>MQTT-PDG-001</code></td>
                </tr>
                <tr>
                    <td><strong>Name</strong></td>
                    <td>Nama deskriptif agar mudah dikenali.</td>
                    <td>Broker Padang Production</td>
                </tr>
                <tr>
                    <td><strong>Broker URL</strong></td>
                    <td>Alamat broker MQTT. Format: <code>mqtt://host:port</code> atau <code>mqtts://host:port</code> untuk SSL.</td>
                    <td><code>mqtt://192.168.3.10:1883</code></td>
                </tr>
                <tr>
                    <td><strong>Username</strong></td>
                    <td>Username autentikasi broker (kosongkan jika broker tanpa auth).</td>
                    <td><code>resq_user</code></td>
                </tr>
                <tr>
                    <td><strong>Password</strong></td>
                    <td>Password autentikasi. Disimpan terenkripsi, tidak akan ditampilkan ulang. Kosongkan saat edit jika tidak ingin mengubah.</td>
                    <td>••••••••</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- SECTION 3: Consumer --}}
<div class="mb-4">
    <h5 class="text-primary"><i class="bx bx-download me-1"></i> Pengaturan Consumer (Menerima Data)</h5>
    <p>Aktifkan Consumer jika server RESQ perlu <strong>menerima</strong> data telemetry dari device/gateway.</p>

    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th style="width:30%">Field</th>
                    <th>Penjelasan</th>
                    <th style="width:30%">Contoh</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Input Topic</strong></td>
                    <td>
                        Topic yang di-subscribe. Gunakan wildcard:
                        <ul class="mb-0 mt-1">
                            <li><code>#</code> — semua level di bawahnya</li>
                            <li><code>+</code> — satu level wildcard</li>
                        </ul>
                    </td>
                    <td><code>resq/telemetry/#</code></td>
                </tr>
                <tr>
                    <td><strong>QoS</strong></td>
                    <td>
                        Quality of Service:
                        <ul class="mb-0 mt-1">
                            <li><strong>0</strong> — At most once (tercepat, bisa hilang)</li>
                            <li><strong>1</strong> — At least once (disarankan)</li>
                            <li><strong>2</strong> — Exactly once (terlambat tapi pasti)</li>
                        </ul>
                    </td>
                    <td><code>0</code> atau <code>1</code></td>
                </tr>
                <tr>
                    <td><strong>Example Output (JSON)</strong></td>
                    <td>
                        <strong class="text-success">Opsional — biarkan kosong.</strong><br>
                        RESQ otomatis generate payload dari sensor + mapping preset yang terpasang di logger.<br>
                        Isi manual hanya jika device pakai format custom yang beda dari default RESQ.
                    </td>
                    <td><em>Kosongkan (plug-and-play)</em></td>
                </tr>
                <tr>
                    <td><strong>Sensor Code JSON Path</strong></td>
                    <td>Path di JSON payload untuk mengambil sensor_code. Gunakan dot-notation untuk nested.</td>
                    <td><code>sensor_code</code> atau <code>device.id</code></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="alert alert-info mt-3 mb-0">
        <i class="bx bx-bulb me-1"></i>
        <strong>Format Payload dari Device:</strong> Device/gateway cukup kirim JSON minimal:
        <pre class="bg-white p-2 rounded mt-2 mb-0"><code>{
    "sensor_code": "SNS-PDG-001",
    "value": 12.4
}</code></pre>
        <p class="mt-2 mb-0">Jika topic menggunakan pola <code>resq/telemetry/SNS-PDG-001</code>, sensor_code juga bisa diambil dari segmen terakhir topic.</p>
    </div>
</div>

{{-- SECTION 4: Producer --}}
<div class="mb-4">
    <h5 class="text-primary"><i class="bx bx-upload me-1"></i> Pengaturan Producer (Mengirim Data)</h5>
    <p>Aktifkan Producer jika RESQ perlu <strong>mengirim/publish</strong> data ke broker (misalnya ke dashboard lain atau sistem peringatan).</p>

    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th style="width:30%">Field</th>
                    <th>Penjelasan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Output Topic</strong></td>
                    <td>Topic tujuan publish. Data sensor canonical dan/atau warning akan dikirim ke sini.</td>
                </tr>
                <tr>
                    <td><strong>Retain</strong></td>
                    <td>Jika aktif, broker menyimpan pesan terakhir. Client baru yang subscribe langsung dapat data terakhir.</td>
                </tr>
                <tr>
                    <td><strong>Publish Canonical</strong></td>
                    <td>Kirim data canonical parameter (hasil mapping sensor) ke broker.</td>
                </tr>
                <tr>
                    <td><strong>Publish Warning Transition</strong></td>
                    <td>Kirim notifikasi saat level peringatan berubah (Normal → Awas, dll).</td>
                </tr>
                <tr>
                    <td><strong>Canonical Parameter Filter</strong></td>
                    <td>Pilih parameter tertentu yang ingin di-publish. Kosong = semua parameter.</td>
                </tr>
                <tr>
                    <td><strong>Warning Level Filter</strong></td>
                    <td>Pilih level warning yang di-publish. Kosong = semua level.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- SECTION 5: Edit Konfigurasi --}}
<div class="mb-4">
    <h5 class="text-primary"><i class="bx bx-edit me-1"></i> Mengedit Konfigurasi</h5>
    <ol>
        <li>Klik tombol <span class="badge bg-primary">Edit</span> pada konfigurasi yang ingin diubah di tabel bawah.</li>
        <li>Form akan terisi otomatis dengan data konfigurasi tersebut.</li>
        <li>Ubah field yang diperlukan.</li>
        <li><strong>Password:</strong> Kosongkan jika tidak ingin mengubah. Isi baru jika ingin mengganti.</li>
        <li>Klik <span class="badge bg-primary">Save / Update Configuration</span> untuk menyimpan.</li>
    </ol>
    <div class="alert alert-warning mb-0">
        <i class="bx bx-error me-1"></i>
        <strong>Perhatian:</strong> Saat edit, section <em>Current Sensor Data (Live)</em> akan muncul di bawah Example Output.
        Ini adalah data realtime dari sensor — <strong>hanya untuk referensi</strong>, bukan untuk diedit.
    </div>
</div>

{{-- SECTION 6: Test MQTT --}}
<div class="mb-4">
    <h5 class="text-primary"><i class="bx bx-check-shield me-1"></i> Test Koneksi MQTT</h5>
    <ol>
        <li>Klik tombol <span class="badge bg-success">Test</span> pada konfigurasi di tabel monitor.</li>
        <li>RESQ akan mencoba connect ke broker dan publish test message.</li>
        <li>Jika berhasil, status akan berubah menjadi <span class="badge bg-success">connected</span>.</li>
    </ol>
    <p><strong>Test dari Data Logger:</strong> Di halaman Data Loggers, tombol <em>Test MQTT from Remote</em> akan SSH ke device, lalu publish dari device langsung ke broker. Terminal log akan menampilkan JSON payload lengkap termasuk semua parameter.</p>
</div>

{{-- SECTION 7: Troubleshooting --}}
<div class="mb-4">
    <h5 class="text-primary"><i class="bx bx-wrench me-1"></i> Troubleshooting</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th style="width:35%">Masalah</th>
                    <th>Solusi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Status <span class="badge bg-danger">error</span></td>
                    <td>Periksa Broker URL, username/password. Pastikan broker bisa diakses dari server RESQ.</td>
                </tr>
                <tr>
                    <td>Data tidak masuk (IN: -)</td>
                    <td>
                        <ul class="mb-0">
                            <li>Pastikan Consumer aktif (toggle ON)</li>
                            <li>Pastikan Input Topic sesuai dengan topic yang dikirim device</li>
                            <li>Pastikan device benar-benar publish ke broker yang sama</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td>Sensor code tidak cocok</td>
                    <td>Periksa <em>Sensor Code JSON Path</em>. Harus sesuai dengan key di JSON payload device.</td>
                </tr>
                <tr>
                    <td>Test berhasil tapi data tetap tidak masuk</td>
                    <td>
                        <ul class="mb-0">
                            <li>Pastikan MQTT Gateway Node.js sudah running (<code>npm run mqtt:gateway</code>)</li>
                            <li>Pastikan <code>MQTT_CALLBACK_URL</code> dan <code>MQTT_CALLBACK_TOKEN</code> di env gateway sesuai</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td>Password lupa / tidak bisa login broker</td>
                    <td>Edit konfigurasi dan isi password baru. Password lama tidak bisa dilihat karena terenkripsi.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- SECTION 8: Arsitektur --}}
<div class="mb-0">
    <h5 class="text-primary"><i class="bx bx-sitemap me-1"></i> Arsitektur Koneksi</h5>
    <pre class="bg-light p-3 rounded" style="font-size:0.75rem;overflow-x:auto"><code>┌─────────────────┐     publish      ┌──────────────┐     subscribe     ┌──────────────────┐
│  Device/Gateway │ ───────────────► │ MQTT Broker  │ ◄─────────────── │  RESQ Gateway    │
│  (RedNode/IoT)  │                  │ (Mosquitto)  │                  │  (Node.js)       │
└─────────────────┘                  └──────────────┘                  └────────┬─────────┘
                                                                                │
                                                                        POST /api/mqtt/ingest
                                                                                │
                                                                       ┌────────▼─────────┐
                                                                       │  RESQ Laravel    │
                                                                       │  (Web Server)    │
                                                                       └──────────────────┘

Topic Pattern:  resq/telemetry/{sensor_code}
Payload:        {"sensor_code": "SNS-PDG-001", "value": 12.4}</code></pre>
</div>
