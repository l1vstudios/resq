# MQTT Master Data — Implementation Summary

> This is the continuation guide for maintainers and AI agents. Read it before
> changing MQTT broker configuration, MQTT ingestion, or MQTT producer output.
> The operational setup guide is [MQTT_CONFIGURATION.md](MQTT_CONFIGURATION.md).

## What was implemented

MQTT broker credentials are now project-owned master data instead of a single
hard-coded broker in environment variables. One configuration can act as a
consumer, a producer, or both:

- A **consumer** subscribes to a broker topic, resolves its sensor, maps JSON
  paths to canonical fields, and sends the result to Laravel for persistence.
- A **producer** publishes canonical observations and alert-level transitions
  through a database-backed outbox. This supports IoT warnings and forwarding
  canonical data to another system without coupling Laravel to a broker
  connection.

The feature is delivered by commits `ff6c53f`, `f4cb7fd`, `a38291c`, and
`4730795`.

## Architecture and data flow

```text
MQTT broker
  -> modbus-server/mqtt-runtime.js (Node gateway)
  -> POST /api/mqtt/ingest (Laravel)
  -> CanonicalMappingService + telemetry/sensor update
  -> mqtt_outbox_messages
  -> Node gateway publishes to MQTT broker
```

The Node gateway, started with `npm run mqtt:gateway`, is intentionally the
only process that maintains MQTT sockets. Laravel persists configuration,
canonical data, telemetry, and outbox records; it does not connect directly to
the broker.

## Source of truth and ownership

| Concern | Source of truth | Main implementation |
| --- | --- | --- |
| Broker configuration | `mqtt_configurations` | `MqttConfiguration`, `MqttConfigurationController` |
| Encrypted broker password | `password_ciphertext` | `MqttCredentialCipher` |
| MQTT sensor assignment | `sensors.input_source` and `sensors.mqtt_configuration_id` | `Sensor`, Project Setup UI |
| Consumer-to-canonical mapping | active `sensor_mapping_profiles.source_parameter` JSON paths | `mqtt-runtime.js`, `MqttConfigurationController::ingest()` |
| Pending/retryable producer messages | `mqtt_outbox_messages` | `MqttOutboxService`, `mqtt-runtime.js` |
| Broker health | status/timestamp/error columns on `mqtt_configurations` | `MqttDatabaseRuntime` |

Schema changes live in
`database/migrations/2026_08_20_000000_create_mqtt_configuration_tables.php`.
Do not rename or remove the status/outbox columns without updating the Node
runtime SQL at the same time.

## Configuration contract

`mqtt_configurations` has these important groups of fields:

- Connection: `broker_url`, `username`, `password_ciphertext`.
- Consumer: `consumer_enabled`, `consumer_topic`, `consumer_qos`,
  `example_payload`, `sensor_code_path`.
- Producer: `producer_enabled`, `producer_topic`, `producer_qos`,
  `producer_retain`, `publish_canonical`, `publish_warning`, filters and
  JSON templates.
- Runtime monitor: `connection_status`, activity timestamps, `last_error`,
  and `runtime_metrics`.

Passwords are AES-256-GCM envelopes created by Laravel and decrypted by Node.
Both processes must have the **same** `MQTT_CREDENTIAL_KEY`: a base64-encoded
32-byte value. Treat rotation as a migration: re-encrypt every stored password
with the new key before deploying the Node process with that key.

MQTT-specific environment variables now configure runtime plumbing only:

| Variable | Purpose | Default |
| --- | --- | --- |
| `MQTT_CREDENTIAL_KEY` | Shared Laravel/Node encryption key | required when password is used |
| `MQTT_CONFIG_REFRESH_MS` | DB configuration reload interval | `5000` |
| `MQTT_OUTBOX_POLL_MS` | Outbox polling interval | `1000` |
| `MQTT_CALLBACK_URL` | Laravel ingestion endpoint | `APP_URL/api/mqtt/ingest` |
| `MQTT_CALLBACK_TOKEN` | Optional Bearer token for ingestion | empty |
| `MQTT_CONNECT_TIMEOUT_MS` | Broker connect timeout | `10000` |
| `MODBUS_BACKEND_URL` | Node gateway base URL used by the UI test button | required for test button |

Do not restore the previous central `MQTT_BROKER_URL`, `MQTT_TOPIC`,
`MQTT_USERNAME`, or `MQTT_PASSWORD` environment configuration. Broker details
belong to the database configuration.

## Consumer behavior

1. In **Project Setup → Sensor & Data**, a user expands **Tambah / Edit MQTT
   Configuration** and creates an active consumer configuration.
2. In the same tab, the user expands **Tambah / Edit Sensor**, selects `MQTT
   Configuration` as `input_source`, and chooses a configuration from the same
   project.
3. Each active mapping profile stores a `source_parameter` JSON path such as
   `data.temperature`. That path is validated against `example_payload` on
   sensor save.
4. The Node runtime resolves the sensor by `sensor_code_path`; if missing, it
   tries `payload.sensor_code`, then the final segment of the MQTT topic.
5. The Node runtime submits only matching mapped values to `/api/mqtt/ingest`.
6. Laravel re-checks active config, sensor/project ownership, and mapping
   before it writes canonical data.

The consumer only accepts a JSON object. A malformed payload, an unknown
sensor, or no matching mapping path updates the configuration error state and
does not create canonical data.

## Producer behavior

`DeviceSetupController` and MQTT ingestion call `MqttOutboxService` after a
canonical observation is created. The service selects active producer configs
within the sensor project and creates one deduplicated outbox message per
eligible configuration.

Eligibility rules:

- Canonical publishing can be restricted with `canonical_parameter_ids`; an
  empty list means all canonical parameters.
- Warning publishing can be restricted with `warning_levels`; an empty list
  means all levels.
- A warning is queued only if `alert_level` changed.

Templates must be valid JSON. Permitted placeholders are validated in
`MqttOutboxService::validateTemplate()`; extend that allow-list and
`baseContext()` together when adding a new placeholder. The Node runtime claims
messages, publishes them with their stored topic/QoS/retain values, then marks
them `published`. Failures become `failed` and retry with exponential backoff,
capped at five minutes.

## HTTP and UI contract

Laravel routes:

- `GET /mqtt-configurations` — configuration page.
- `POST /mqtt-configurations` — create/update by `configuration_code`.
- `DELETE /mqtt-configurations/{configuration}` — blocked while used by a
  sensor.
- `GET /mqtt-configurations/status` — stored monitor status.
- `POST /mqtt-configurations/{configuration}/test` — proxies a test request to
  the Node gateway.
- `POST /api/mqtt/ingest` — internal callback from the Node gateway; accepts
  `mqtt_configuration_id`, `sensor_code`, `topic`, raw `payload`, mapped
  `values`, and optional `observed_at`.

Node gateway routes in `modbus-server/server.js`:

- `GET /api/mqtt/configurations/status`
- `POST /api/mqtt/configurations/:configurationId/test`

The MQTT form, Monitor, and Test button are surfaced in **Project Setup → Sensor
& Data**. The legacy standalone URL remains available for compatibility, but is
no longer linked from the sidebar. The test endpoint does not publish to
wildcard topics (`+` or `#`); it reports a successful connection and skips the
publish instead.

## Safe change checklist

Before changing this feature:

1. Keep Laravel and Node encryption formats compatible. Test both encryption
   and decryption when changing `MqttCredentialCipher` or `decryptCredential`.
2. When altering tables, update raw SQL in `mqtt-runtime.js` for both MySQL and
   PostgreSQL placeholder behavior.
3. Preserve project authorization in the controller and the project/sensor
   consistency check in ingestion.
4. Preserve the outbox pattern; do not publish synchronously from Laravel.
5. Add/adjust a Laravel feature test and `modbus-server/mqtt-runtime.test.js`.
6. Update `MQTT_CONFIGURATION.md` if an operator-facing setup step changes.

## Verification commands

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test
npm run test:mqtt
php artisan view:cache
```

For database schema verification, run the migration using the deployment DB
driver (MySQL or PostgreSQL), then start `npm run mqtt:gateway` and create an
active configuration through the UI. Confirm that its monitor status reaches
`connected` and that a test publish or a real message updates the timestamps.
