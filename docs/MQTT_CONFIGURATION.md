# MQTT Configuration

## Runtime prerequisites

The `modbus-server` MQTT runtime reads active broker configuration directly
from the application database. It supports `DB_CONNECTION=mysql` and
`DB_CONNECTION=pgsql`.

Set the same `MQTT_CREDENTIAL_KEY` in Laravel and in the Node runtime. The
value must be base64-encoded 32 bytes:

```bash
openssl rand -base64 32
```

Run the database migration and start the runtime:

```bash
php artisan migrate
npm run mqtt:gateway
```

`MQTT_CALLBACK_URL` defaults to `APP_URL/api/mqtt/ingest`. Use
`MQTT_CALLBACK_TOKEN` when the ingestion endpoint needs bearer-token
authentication. `MODBUS_BACKEND_URL` is only needed by the browser's Test
button to reach the running Node gateway.

## Configure a broker

Open **Configuration → MQTT Configuration** and create a configuration for a
project. Broker URL, username, and password belong to that project. Passwords
are encrypted with AES-256-GCM before they are stored; an existing password is
preserved when the edit form leaves the password field empty.

Enable the required direction:

- Consumer: set input topic, QoS, example JSON payload, and optional JSON path
  containing the sensor code.
- Producer: set output topic, QoS, retain, event filters, and JSON templates
  for canonical and warning payloads.

An active configuration is picked up automatically by the Node runtime on the
next refresh interval (`MQTT_CONFIG_REFRESH_MS`, default five seconds).

## Connect a sensor

In **Project Setup → Sensor Data**, choose **MQTT Configuration** as the input
source and select an active consumer configuration from the same project.
Then map each canonical field using its JSON path as the source parameter, for
example `data.temperature`. The path is validated against the saved example
payload when the sensor is saved.

For a shared topic, the runtime resolves the sensor using the configured
sensor-code path. If it is absent, it falls back to payload `sensor_code` and
then to the last topic segment.

## Producer delivery and monitoring

Canonical observations and warning-level transitions are put in the MQTT
outbox. The Node runtime publishes them with the stored topic/QoS/retain
settings, marks successful delivery, and retries failures with backoff.

Use the MQTT Configuration monitor to check connection state, last inbound and
outbound activity, runtime errors, and to send a test publish. A wildcard-only
consumer configuration reports a successful connection but skips test publish,
because MQTT wildcards cannot be published to.
