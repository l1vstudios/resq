@extends('layouts.master')

@section('title') Panduan Production @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Panduan @endslot
@slot('title') MQTT & RedNode Production @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-2">Panduan Production MQTT & RedNode</h4>
                <p class="text-muted mb-0">Setup production untuk memisahkan server web RESQ, MQTT broker, dan server Node-RED/RedNode di lapangan.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-3"><i class="bx bx-server me-1 text-primary"></i> Server Web RESQ</h5>
                <p>Server web menjalankan Laravel, broker MQTT, dan gateway monitor MQTT lokal.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Komponen</th>
                                <th>Fungsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Laravel + PHP-FPM</strong></td>
                                <td>Dashboard, API callback, config RedNode, dan penyimpanan telemetry.</td>
                            </tr>
                            <tr>
                                <td><strong>Mosquitto</strong></td>
                                <td>MQTT broker yang menerima publish dari RedNode.</td>
                            </tr>
                            <tr>
                                <td><strong>modbus-server/server.js</strong></td>
                                <td>Gateway HTTP lokal port <code>3100</code> untuk status/test MQTT dari dashboard.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-3"><i class="bx bx-chip me-1 text-success"></i> Server Node-RED / RedNode</h5>
                <p>Server lapangan membaca sensor serial/Modbus lalu mengirim data ke server web.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Komponen</th>
                                <th>Fungsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>rednode-gateway</strong></td>
                                <td>Baca sensor, kirim HTTP callback, publish MQTT telemetry.</td>
                            </tr>
                            <tr>
                                <td><strong>Node-RED</strong></td>
                                <td>Flow tambahan jika dibutuhkan. RedNode gateway tetap service utama untuk sensor.</td>
                            </tr>
                            <tr>
                                <td><strong>Internet/LAN</strong></td>
                                <td>Butuh internet jika server web ada di VPS. Tidak butuh kuota jika semua satu LAN.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3"><i class="bx bx-sitemap me-1 text-info"></i> Alur Data</h5>
        <pre class="bg-light p-3 rounded mb-0" style="font-size: .82rem; overflow-x: auto;"><code>Sensor Modbus
  -> rednode-gateway di server Node-RED
  -> HTTP callback ke Laravel: /api/realtime-sensor-status
  -> MQTT publish ke broker: resq/telemetry/{sensor_code}
  -> MQTT gateway RESQ subscribe broker
  -> Laravel menerima MQTT ingest: /api/mqtt/ingest</code></pre>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-3">1. Setup Server Web</h5>
                <p class="text-muted">Install dependency production di Ubuntu server web.</p>
                <pre class="bg-light p-3 rounded" style="font-size: .82rem; overflow-x: auto;"><code>sudo apt update
sudo apt install nginx php-fpm php-cli php-mysql mysql-server nodejs npm mosquitto mosquitto-clients
sudo systemctl enable mosquitto
sudo systemctl start mosquitto</code></pre>

                <p class="text-muted">ENV penting di server web.</p>
                <pre class="bg-light p-3 rounded mb-0" style="font-size: .82rem; overflow-x: auto;"><code>APP_URL=https://domain-web-kamu.com

MODBUS_BACKEND_PORT=3100
MODBUS_BACKEND_URL=http://127.0.0.1:3100

MQTT_BROKER_URL=mqtt://127.0.0.1:1883
MQTT_USERNAME=
MQTT_PASSWORD=
MQTT_CALLBACK_URL=https://domain-web-kamu.com/api/mqtt/ingest
MQTT_INGEST_RATE_LIMIT_PER_MINUTE=6000</code></pre>

                <div class="alert alert-info mt-3 mb-0">
                    <h6 class="alert-heading mb-2">Isi MQTT broker URL dan topic</h6>
                    <p class="mb-2">Jika broker Mosquitto berada di server web yang sama, isi broker lokal dengan <code>mqtt://127.0.0.1:1883</code>. Untuk koneksi dari RedNode, gunakan IP/domain server web yang bisa dijangkau dari server Node-RED, misalnya <code>mqtt://192.168.3.10:1883</code>.</p>
                    <p class="mb-0">Topic subscribe di menu MQTT Configuration gunakan <code>resq/telemetry/#</code>. Prefix publish di RedNode gunakan <code>resq/telemetry</code> tanpa <code>/#</code>, karena gateway otomatis menambahkan <code>/{sensor_code}</code>.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-3">2. Setup Server Node-RED / RedNode</h5>
                <p class="text-muted">Pastikan folder gateway ada di server lapangan.</p>
                <pre class="bg-light p-3 rounded" style="font-size: .82rem; overflow-x: auto;"><code>cd /root/rednode-gateway
npm install
node gateway.js</code></pre>

                <div class="alert alert-info">
                    <h6 class="alert-heading mb-2">Jika service sudah ada</h6>
                    <p class="mb-2">Tidak perlu menjalankan <code>node gateway.js</code> manual. Cukup edit file <code>/root/rednode-gateway/.env</code>, lalu restart service agar perubahan ENV terbaca.</p>
                    <pre class="bg-light p-3 rounded mb-0" style="font-size: .82rem; overflow-x: auto;"><code>cd /root/rednode-gateway
nano .env
systemctl restart rednode-gateway
systemctl status rednode-gateway --no-pager
tail -f /root/rednode-gateway/gateway-*.log</code></pre>
                </div>

                <p class="text-muted">ENV penting di server Node-RED/RedNode.</p>
                <pre class="bg-light p-3 rounded mb-0" style="font-size: .82rem; overflow-x: auto;"><code>APP_URL=https://domain-web-kamu.com
REDNODE_CONFIG_URL=https://domain-web-kamu.com/api/rednode/config
REDNODE_CALLBACK_URL=https://domain-web-kamu.com/api/realtime-sensor-status
REDNODE_HEARTBEAT_URL=https://domain-web-kamu.com/api/rednode/heartbeat

REDNODE_MQTT_ENABLED=true
REDNODE_MQTT_BROKER_URL=mqtt://IP-SERVER-WEB:1883
REDNODE_MQTT_TOPIC_PREFIX=resq/telemetry
REDNODE_LOGGER_CODE=KODE-LOGGER</code></pre>

                <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Field</th>
                                <th>Isi Production</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>REDNODE_MQTT_BROKER_URL</code></td>
                                <td><code>mqtt://IP-SERVER-WEB:1883</code>, contoh <code>mqtt://192.168.3.10:1883</code>.</td>
                            </tr>
                            <tr>
                                <td><code>REDNODE_MQTT_TOPIC_PREFIX</code></td>
                                <td><code>resq/telemetry</code>. Nanti payload masuk ke topic <code>resq/telemetry/KODE-SENSOR</code>.</td>
                            </tr>
                            <tr>
                                <td>Consumer topic di dashboard</td>
                                <td><code>resq/telemetry/#</code>, supaya semua sensor di bawah prefix tersebut ikut terbaca.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3"><i class="bx bx-slider-alt me-1 text-primary"></i> Setting Dashboard MQTT Configuration</h5>
        <p class="text-muted">Isi konfigurasi ini di menu <strong>Project Setup</strong> tab <strong>Sensor & Data</strong>, bagian <strong>Tambah / Edit MQTT Configuration</strong>.</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Field</th>
                        <th>Isi</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Broker URL</strong></td>
                        <td><code>mqtt://127.0.0.1:1883</code></td>
                        <td>Dipakai jika Mosquitto berjalan di server web yang sama.</td>
                    </tr>
                    <tr>
                        <td><strong>Consumer Topic</strong></td>
                        <td><code>resq/telemetry/#</code></td>
                        <td>Wildcard <code>#</code> membaca semua sensor di bawah prefix <code>resq/telemetry</code>.</td>
                    </tr>
                    <tr>
                        <td><strong>Consumer QoS</strong></td>
                        <td><code>0</code></td>
                        <td>Cukup untuk telemetry periodik; data berikutnya akan tetap masuk jika ada packet yang lewat.</td>
                    </tr>
                    <tr>
                        <td><strong>Consumer Enabled</strong></td>
                        <td><span class="badge bg-success">ON</span></td>
                        <td>Wajib aktif supaya gateway web subscribe ke topic MQTT.</td>
                    </tr>
                    <tr>
                        <td><strong>Active / auto-connect</strong></td>
                        <td><span class="badge bg-success">ON</span></td>
                        <td>Wajib aktif supaya konfigurasi otomatis di-connect oleh gateway.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-3">3. Service Production</h5>
                <p class="text-muted">Jalankan gateway RESQ sebagai systemd service di server web.</p>
                <pre class="bg-light p-3 rounded" style="font-size: .82rem; overflow-x: auto;"><code>sudo nano /etc/systemd/system/resq-mqtt-gateway.service</code></pre>
                <pre class="bg-light p-3 rounded" style="font-size: .82rem; overflow-x: auto;"><code>[Unit]
Description=RESQ MQTT Gateway
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
WorkingDirectory=/var/www/resq
ExecStart=/usr/bin/node /var/www/resq/modbus-server/server.js
Restart=always
RestartSec=5
User=www-data

[Install]
WantedBy=multi-user.target</code></pre>
                <pre class="bg-light p-3 rounded mb-0" style="font-size: .82rem; overflow-x: auto;"><code>sudo systemctl daemon-reload
sudo systemctl enable resq-mqtt-gateway
sudo systemctl start resq-mqtt-gateway</code></pre>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-3">4. Service RedNode</h5>
                <p class="text-muted">Di server Node-RED/RedNode, jalankan gateway sensor sebagai service. Jika service sudah ada, bagian ini cukup dipakai untuk cek isi service dan restart.</p>
                <pre class="bg-light p-3 rounded" style="font-size: .82rem; overflow-x: auto;"><code>systemctl status rednode-gateway --no-pager
systemctl cat rednode-gateway --no-pager</code></pre>
                <pre class="bg-light p-3 rounded" style="font-size: .82rem; overflow-x: auto;"><code>sudo nano /etc/systemd/system/rednode-gateway.service</code></pre>
                <pre class="bg-light p-3 rounded" style="font-size: .82rem; overflow-x: auto;"><code>[Unit]
Description=RedNode Gateway
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
WorkingDirectory=/root/rednode-gateway
EnvironmentFile=/root/rednode-gateway/.env
ExecStart=/usr/bin/node /root/rednode-gateway/gateway.js
Restart=always
RestartSec=5
User=root

[Install]
WantedBy=multi-user.target</code></pre>
                <pre class="bg-light p-3 rounded mb-0" style="font-size: .82rem; overflow-x: auto;"><code>sudo systemctl daemon-reload
sudo systemctl enable rednode-gateway
sudo systemctl restart rednode-gateway</code></pre>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3"><i class="bx bx-check-shield me-1 text-success"></i> Checklist Test</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Lokasi</th>
                        <th>Command</th>
                        <th>Hasil Normal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Server web</td>
                        <td><code>systemctl status mosquitto</code></td>
                        <td>Mosquitto active/running.</td>
                    </tr>
                    <tr>
                        <td>Server web</td>
                        <td><code>curl http://127.0.0.1:3100/health</code></td>
                        <td>Response JSON <code>{"ok":true}</code>.</td>
                    </tr>
                    <tr>
                        <td>Server RedNode</td>
                        <td><code>systemctl status rednode-gateway</code></td>
                        <td>Service active/running.</td>
                    </tr>
                    <tr>
                        <td>Server RedNode</td>
                        <td><code>tail -f /root/rednode-gateway/gateway-*.log</code></td>
                        <td>Ada log sensor, callback terkirim, dan MQTT connected.</td>
                    </tr>
                    <tr>
                        <td>Dashboard RESQ</td>
                        <td>Menu MQTT Configuration, klik Refresh/Test.</td>
                        <td>Status connected dan Last Activity bergerak.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-warning">
    <h5 class="alert-heading">Catatan Security Production</h5>
    <p class="mb-0">Jika broker MQTT dibuka ke internet, jangan gunakan anonymous access. Aktifkan username/password, batasi firewall hanya dari IP RedNode, dan gunakan TLS bila memungkinkan.</p>
</div>
@endsection
