const crypto = require('crypto');
const mqtt = require('mqtt');

function jsonValue(value, fallback = null) {
  if (value === null || value === undefined || value === '') return fallback;
  if (typeof value === 'object') return value;
  try { return JSON.parse(value); } catch (_) { return fallback; }
}

function dataGet(value, path) {
  if (!path) return undefined;
  return String(path).split('.').reduce((current, key) => (
    current !== null && current !== undefined ? current[key] : undefined
  ), value);
}

function topicSensorCode(topic) {
  const parts = String(topic || '').split('/').filter(Boolean);
  return parts[parts.length - 1] || null;
}

function decryptCredential(envelope) {
  if (!envelope) return undefined;
  const encodedKey = String(process.env.MQTT_CREDENTIAL_KEY || '');
  const key = Buffer.from(encodedKey, 'base64');
  if (key.length !== 32) throw new Error('MQTT_CREDENTIAL_KEY wajib berupa base64 dari 32 byte.');
  const parts = String(envelope).split(':');
  if (parts.length !== 4 || parts[0] !== 'v1') throw new Error('Format credential MQTT tidak dikenali.');
  const decipher = crypto.createDecipheriv('aes-256-gcm', key, Buffer.from(parts[1], 'base64'));
  decipher.setAuthTag(Buffer.from(parts[2], 'base64'));
  return Buffer.concat([decipher.update(Buffer.from(parts[3], 'base64')), decipher.final()]).toString('utf8');
}

async function createDatabase() {
  const driver = String(process.env.DB_CONNECTION || 'mysql').toLowerCase();
  const common = {
    host: process.env.DB_HOST || '127.0.0.1',
    port: Number(process.env.DB_PORT || (driver === 'pgsql' ? 5432 : 3306)),
    database: process.env.DB_DATABASE,
    user: process.env.DB_USERNAME,
    password: process.env.DB_PASSWORD || '',
  };

  if (driver === 'pgsql' || driver === 'postgres' || driver === 'postgresql') {
    const { Pool } = require('pg');
    const pool = new Pool(common);
    return {
      driver: 'pgsql',
      async query(sql, params = []) {
        let index = 0;
        const pgSql = sql.replace(/\?/g, () => `$${++index}`);
        return (await pool.query(pgSql, params)).rows;
      },
      close: () => pool.end(),
    };
  }

  if (driver !== 'mysql' && driver !== 'mariadb') {
    throw new Error(`DB_CONNECTION ${driver} belum didukung MQTT runtime; gunakan mysql atau pgsql.`);
  }
  const mysql = require('mysql2/promise');
  const pool = mysql.createPool({ ...common, connectionLimit: 5 });
  return {
    driver: 'mysql',
    async query(sql, params = []) { return (await pool.execute(sql, params))[0]; },
    close: () => pool.end(),
  };
}

class MqttDatabaseRuntime {
  constructor(options = {}) {
    this.clients = new Map();
    this.db = null;
    this.refreshTimer = null;
    this.outboxTimer = null;
    this.callbackUrl = options.callbackUrl
      || process.env.MQTT_CALLBACK_URL
      || `${String(process.env.APP_URL || 'http://127.0.0.1:8000').replace(/\/$/, '')}/api/mqtt/ingest`;
    this.callbackToken = process.env.MQTT_CALLBACK_TOKEN || process.env.MODBUS_CALLBACK_TOKEN || '';
    this.refreshMs = Math.max(Number(process.env.MQTT_CONFIG_REFRESH_MS || 5000), 1000);
    this.outboxMs = Math.max(Number(process.env.MQTT_OUTBOX_POLL_MS || 1000), 250);
  }

  async start() {
    this.db = await createDatabase();
    await this.refresh();
    this.refreshTimer = setInterval(() => this.refresh().catch((error) => this.logError('refresh', error)), this.refreshMs);
    this.outboxTimer = setInterval(() => this.drainOutbox().catch((error) => this.logError('outbox', error)), this.outboxMs);
    console.log(`[mqtt-db] runtime started (${this.db.driver}, refresh ${this.refreshMs}ms)`);
  }

  async stop() {
    clearInterval(this.refreshTimer);
    clearInterval(this.outboxTimer);
    for (const state of this.clients.values()) state.client.end(true);
    this.clients.clear();
    if (this.db) await this.db.close();
  }

  statuses() {
    return [...this.clients.entries()].map(([id, state]) => ({ id, connected: state.connected, code: state.config.configuration_code }));
  }

  async refresh() {
    const configs = await this.db.query('SELECT * FROM mqtt_configurations WHERE is_active = ?', [true]);
    const activeIds = new Set(configs.map((config) => Number(config.id)));
    for (const [id, state] of this.clients.entries()) {
      if (!activeIds.has(id)) {
        state.client.end(true);
        this.clients.delete(id);
        await this.updateStatus(id, 'inactive', null);
      }
    }

    for (const config of configs) {
      const id = Number(config.id);
      const signature = crypto.createHash('sha256').update(JSON.stringify([
        config.broker_url, config.username, config.password_ciphertext, config.consumer_enabled,
        config.consumer_topic, config.consumer_qos, config.producer_enabled, config.producer_topic,
        config.producer_qos, config.producer_retain, config.sensor_code_path,
      ])).digest('hex');
      if (this.clients.get(id)?.signature === signature) continue;
      await this.connectConfig(config, signature);
    }
  }

  async connectConfig(config, signature) {
    const id = Number(config.id);
    const previous = this.clients.get(id);
    if (previous) previous.client.end(true);
    const sensors = await this.loadSensors(id);
    let password;
    try { password = decryptCredential(config.password_ciphertext); } catch (error) {
      await this.updateStatus(id, 'error', error.message);
      return;
    }
    const client = mqtt.connect(config.broker_url, {
      username: config.username || undefined,
      password,
      reconnectPeriod: 2000,
      connectTimeout: Number(process.env.MQTT_CONNECT_TIMEOUT_MS || 10000),
    });
    const state = { client, config, signature, sensors, connected: false, received: 0, published: 0 };
    this.clients.set(id, state);
    await this.updateStatus(id, 'connecting', null);

    client.on('connect', async () => {
      state.connected = true;
      if (Boolean(config.consumer_enabled) && config.consumer_topic) {
        client.subscribe(config.consumer_topic, { qos: Number(config.consumer_qos || 0) }, async (error) => {
          if (error) await this.updateStatus(id, 'error', error.message);
        });
      }
      await this.updateStatus(id, 'connected', null, 'last_connected_at');
    });
    client.on('reconnect', () => { state.connected = false; this.updateStatus(id, 'reconnecting', null).catch(() => {}); });
    client.on('close', () => { state.connected = false; });
    client.on('error', (error) => { state.connected = false; this.updateStatus(id, 'error', error.message).catch(() => {}); });
    client.on('message', (topic, buffer) => this.consume(state, topic, buffer).catch((error) => {
      this.updateStatus(id, 'error', error.message).catch(() => {});
    }));
  }

  async loadSensors(configurationId) {
    const rows = await this.db.query(`
      SELECT s.id, s.sensor_code, smp.source_parameter
      FROM sensors s
      LEFT JOIN sensor_mapping_profiles smp ON smp.sensor_id = s.id AND smp.status = ?
      WHERE s.mqtt_configuration_id = ? AND s.input_source = ?
      ORDER BY s.id, smp.id
    `, ['active', configurationId, 'mqtt']);
    const sensors = new Map();
    rows.forEach((row) => {
      if (!sensors.has(row.sensor_code)) sensors.set(row.sensor_code, { id: Number(row.id), code: row.sensor_code, paths: [] });
      if (row.source_parameter) sensors.get(row.sensor_code).paths.push(row.source_parameter);
    });
    return sensors;
  }

  async consume(state, topic, buffer) {
    let payload;
    try { payload = JSON.parse(buffer.toString('utf8')); } catch (_) { throw new Error(`Payload MQTT bukan JSON valid pada ${topic}.`); }
    if (!payload || typeof payload !== 'object' || Array.isArray(payload)) throw new Error(`Payload MQTT harus JSON object pada ${topic}.`);
    const sensorCode = dataGet(payload, state.config.sensor_code_path || 'sensor_code')
      || payload.sensor_code || topicSensorCode(topic);
    const sensor = state.sensors.get(String(sensorCode));
    if (!sensor) throw new Error(`Sensor ${sensorCode || '-'} tidak terdaftar pada config ${state.config.configuration_code}.`);
    const values = sensor.paths.map((path) => ({ source_path: path, value: dataGet(payload, path) }))
      .filter((item) => item.value !== undefined && item.value !== null);
    if (!values.length) throw new Error(`Tidak ada JSON path mapping yang cocok untuk sensor ${sensor.code}.`);
    const observedAt = payload.observed_at || payload.timestamp || payload.recorded_at || null;
    const response = await fetch(this.callbackUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...(this.callbackToken ? { Authorization: `Bearer ${this.callbackToken}` } : {}) },
      body: JSON.stringify({ mqtt_configuration_id: Number(state.config.id), sensor_code: sensor.code, topic, payload, values, observed_at: observedAt }),
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok || body.ok === false) throw new Error(body.message || `MQTT ingestion HTTP ${response.status}.`);
    state.received += 1;
    await this.db.query('UPDATE mqtt_configurations SET last_received_at = ?, last_error = NULL, runtime_metrics = ?, updated_at = ? WHERE id = ?', [
      new Date(), JSON.stringify({ received: state.received, published: state.published }), new Date(), Number(state.config.id),
    ]);
  }

  async drainOutbox() {
    const rows = await this.db.query(`
      SELECT * FROM mqtt_outbox_messages
      WHERE status IN (?, ?) AND (available_at IS NULL OR available_at <= ?)
      ORDER BY id LIMIT 25
    `, ['pending', 'failed', new Date()]);
    for (const message of rows) await this.publishOutbox(message);
  }

  async publishOutbox(message) {
    const state = this.clients.get(Number(message.mqtt_configuration_id));
    if (!state?.connected || !Boolean(state.config.producer_enabled)) return;
    const claimed = await this.db.query('UPDATE mqtt_outbox_messages SET status = ?, attempts = attempts + 1, updated_at = ? WHERE id = ? AND status IN (?, ?)', [
      'processing', new Date(), Number(message.id), 'pending', 'failed',
    ]);
    const affected = this.db.driver === 'mysql' ? claimed.affectedRows : claimed.length;
    if (!affected && this.db.driver === 'mysql') return;
    try {
      await new Promise((resolve, reject) => state.client.publish(
        message.topic,
        JSON.stringify(jsonValue(message.payload, {})),
        { qos: Number(message.qos || 0), retain: Boolean(message.retain) },
        (error) => error ? reject(error) : resolve()
      ));
      state.published += 1;
      await this.db.query('UPDATE mqtt_outbox_messages SET status = ?, published_at = ?, last_error = NULL, updated_at = ? WHERE id = ?', ['published', new Date(), new Date(), Number(message.id)]);
      await this.db.query('UPDATE mqtt_configurations SET last_published_at = ?, last_error = NULL, runtime_metrics = ?, updated_at = ? WHERE id = ?', [new Date(), JSON.stringify({ received: state.received, published: state.published }), new Date(), Number(state.config.id)]);
    } catch (error) {
      const attempts = Number(message.attempts || 0) + 1;
      const delaySeconds = Math.min(300, 2 ** Math.min(attempts, 8));
      await this.db.query('UPDATE mqtt_outbox_messages SET status = ?, available_at = ?, last_error = ?, updated_at = ? WHERE id = ?', ['failed', new Date(Date.now() + delaySeconds * 1000), error.message, new Date(), Number(message.id)]);
      await this.updateStatus(Number(state.config.id), 'error', error.message);
    }
  }

  async testPublish(configurationId) {
    const state = this.clients.get(Number(configurationId));
    if (!state?.connected) throw new Error('MQTT configuration belum connected.');
    const topic = state.config.producer_topic || state.config.consumer_topic;
    if (!topic || topic.includes('#') || topic.includes('+')) {
      return { ok: true, connected: true, topic: null, message: 'Koneksi broker aktif; test publish dilewati karena topic memakai wildcard.' };
    }
    await new Promise((resolve, reject) => state.client.publish(topic, JSON.stringify({ event: 'connection_test', configuration: state.config.configuration_code, sent_at: new Date().toISOString() }), { qos: Number(state.config.producer_qos || 0), retain: false }, (error) => error ? reject(error) : resolve()));
    return { ok: true, topic };
  }

  async updateStatus(id, status, error = null, timestampField = null) {
    const timestampSql = timestampField ? `, ${timestampField} = ?` : '';
    const params = [status, error];
    if (timestampField) params.push(new Date());
    params.push(new Date(), id);
    await this.db.query(`UPDATE mqtt_configurations SET connection_status = ?, last_error = ?${timestampSql}, updated_at = ? WHERE id = ?`, params);
  }

  logError(area, error) { console.error(`[mqtt-db:${area}] ${error.message}`); }
}

module.exports = { MqttDatabaseRuntime, decryptCredential, dataGet };
