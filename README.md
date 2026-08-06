<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## RESQ MQTT Gateway

Untuk koneksi online tanpa LAN/RJ45, jalankan gateway MQTT di server yang bisa akses internet:

```bash
npm run mqtt:gateway
```

Contoh `.env`:

```dotenv
APP_URL=https://resq.example.com
MQTT_CALLBACK_TOKEN=isi-token-rahasia
MQTT_AUTOSTART=true
MQTT_BROKER_URL=mqtts://broker.example.com:8883
MQTT_TOPIC=resq/telemetry/#
MQTT_USERNAME=
MQTT_PASSWORD=
MQTT_CALLBACK_URL=
```

Payload yang dikirim device ke topic MQTT:

```json
{
  "sensor_code": "SNS-PDG-001",
  "value": 12.4
}
```

Jika topic memakai pola `resq/telemetry/SNS-PDG-001`, `sensor_code` juga bisa diambil dari segmen terakhir topic. Laravel akan menyimpan telemetry dan menghitung status `Awas` saat nilai melewati threshold sensor.

## RESQ RedNode / Bliiot Gateway

Untuk RedNode Bliiot yang membaca sensor RS485 Modbus RTU langsung dari serial, web RESQ sekarang menyediakan konfigurasi di:

```text
GET /api/rednode/config
```

Laravel akan memilih Data Logger dari database berdasarkan IP RedNode yang tersimpan di `rednode_host` pada Connectivity atau `remote_host` pada Data Logger. Jika perlu override manual, endpoint tetap bisa dipanggil dengan `?logger_code=DL-PDG-001`.

Runner RedNode akan mengambil `slave_id`, `address`, `function_code`, `quantity`, `poll_interval_ms`, `threshold`, `scale_factor`, `offset`, dan `unit` dari sensor yang dipilih di tab RedNode. Hasil polling dikirim balik ke Laravel lewat `/api/realtime-sensor-status`, jadi telemetry langsung masuk database.

Port serial RedNode juga disimpan dari web di halaman Modbus Configuration. Mapping bawaan BL118:

```text
PIN 1-2 = /dev/ttyAS4
PIN 3-4 = /dev/ttyAS5
PIN 5-6 = /dev/ttyAS2
PIN 7-8 = /dev/ttyAS3
```

Gunakan script test pin di RedNode untuk memastikan kabel sensor sedang masuk ke pasangan pin yang benar, lalu pilih port tersebut di web.

```bash
npm run rednode:test-pins
```

Untuk test semua port dari web, pastikan file `test-ports.js` juga ada di folder gateway RedNode. Tombol **Test All Ports** akan menghentikan gateway sementara, mencoba semua port BL118, lalu menampilkan TX/RX per sensor di tabel web.

```bash
npm run rednode:test-ports
```

Contoh env untuk dijalankan di RedNode:

```dotenv
APP_URL=http://IP-LAPTOP-ATAU-SERVER:8000
REDNODE_CONFIG_URL=http://IP-LAPTOP-ATAU-SERVER:8000/api/rednode/config
REDNODE_CALLBACK_URL=http://IP-LAPTOP-ATAU-SERVER:8000/api/realtime-sensor-status
REDNODE_HEARTBEAT_URL=http://IP-LAPTOP-ATAU-SERVER:8000/api/rednode/heartbeat
REDNODE_HEARTBEAT_MS=1000
REDNODE_CONFIG_REFRESH_MS=5000
REDNODE_POLL_INTERVAL_MS=1000
# Opsional. Kosongkan agar logger dipilih dari database berdasarkan identity/claim device.
# REDNODE_LOGGER_CODE=DL-PDG-001
# Opsional tapi disarankan untuk multi logger via internet.
# REDNODE_DEVICE_UID=RN-BL118-0001
# REDNODE_SERIAL_NUMBER=BL118-0001
# REDNODE_FIRMWARE_VERSION=BL118-1.0.0
REDNODE_SSH_HOST=192.168.3.1
REDNODE_SSH_PORT=22
REDNODE_SSH_USER=root
REDNODE_SSH_PASSWORD=PASSWORD_SSH_RED_NODE
REDNODE_GATEWAY_PATH=/root/rednode-gateway

# Opsional kalau data juga mau dipublish ke broker MQTT
REDNODE_MQTT_ENABLED=true
REDNODE_MQTT_BROKER_URL=mqtt://139.59.100.220:1883
REDNODE_MQTT_TOPIC_PREFIX=resq/telemetry
REDNODE_MQTT_USERNAME=m100_logger
REDNODE_MQTT_PASSWORD=PASSWORD_MQTT
```

Jalankan gateway:

```bash
npm run rednode:gateway
# atau di folder /root/rednode-gateway:
node gateway.js
```

Di halaman **Data Loggers**, tombol **Start Development** dan **Start Production** akan SSH ke logger, mengubah `.env` di `REDNODE_GATEWAY_PATH`, lalu restart gateway. Set URL target di `.env` server:

```dotenv
REDNODE_DEVELOPMENT_APP_URL=http://192.168.3.10:8000
REDNODE_PRODUCTION_APP_URL=http://139.59.100.220
```

Jika `MQTT_CALLBACK_TOKEN`, `MODBUS_CALLBACK_TOKEN`, atau `REDNODE_CONFIG_TOKEN` diisi di Laravel, samakan token tersebut di env RedNode supaya device boleh mengambil konfigurasi dan mengirim telemetry.

Jika logger code dikosongkan, gateway mengirim identity perangkat seperti UID,
serial number, firmware, hostname, dan MAC address saat mengambil config. Device
yang belum dikenal akan muncul di Data Loggers sebagai Detected Gateway Devices,
lalu bisa di-claim/simpan sebagai Data Logger yang benar.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 1500 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[CMS Max](https://www.cmsmax.com/)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
