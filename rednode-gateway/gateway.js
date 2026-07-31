require('dotenv').config({ quiet: true });

const http = require('http');
const https = require('https');
const crypto = require('crypto');
const mqtt = require('mqtt');
const ModbusRTU = require('modbus-serial');

const configUrl = process.env.REDNODE_CONFIG_URL
  || `${String(process.env.APP_URL || 'http://127.0.0.1:8000').replace(/\/$/, '')}/api/rednode/config`;
const configToken = process.env.REDNODE_CONFIG_TOKEN || '';
const callbackToken = process.env.REDNODE_CALLBACK_TOKEN || '';
const loggerCode = process.env.REDNODE_LOGGER_CODE || 'REDNODE-BLIIOT-01';
const configRefreshMs = numberEnv('REDNODE_CONFIG_REFRESH_MS', 5000);
const loopTickMs = numberEnv('REDNODE_LOOP_TICK_MS', 250);
const heartbeatMs = numberEnv('REDNODE_HEARTBEAT_MS', 1000);

let modbus = new ModbusRTU();
let activeSerialKey = null;
let activeConfig = null;
let mqttClient = null;
let mqttConnected = false;
let mqttBrokerUrl = null;
let running = false;
let sensorState = new Map();
let lastHeartbeatAt = 0;
let pendingHeartbeatReport = null;

function numberEnv(name, fallback) {
  const value = Number(process.env[name]);
  return Number.isFinite(value) ? value : fallback;
}

function normalizeFunctionCode(value, fallback = 'FC03') {
  const normalized = String(value || fallback).toUpperCase().replace(/[^0-9]/g, '');
  return `FC${normalized.padStart(2, '0')}`;
}

function stableId(prefix, facts) {
  const digest = crypto.createHash('sha256').update(JSON.stringify(facts)).digest('hex');
  return `${prefix}-${digest}`;
}

function numericFromText(value) {
  if (value === undefined || value === null || value === '') {
    return null;
  }

  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }

  const match = String(value).replace(',', '.').match(/-?\d+(\.\d+)?/);
  return match ? Number(match[0]) : null;
}

function unitSuffix(sensor) {
  const unit = String(sensor.unit || '').trim();
  return unit && unit !== '0' ? ` ${unit}` : '';
}

function weatherParameterLabel(parameter) {
  return {
    temperature: 'Suhu',
    humidity: 'Kelembapan',
    pressure: 'Tekanan Udara',
    wind_speed: 'Kecepatan Angin',
    wind_direction: 'Arah Angin',
    rainfall: 'Curah Hujan',
    solar_radiation: 'Radiasi Matahari',
    battery_voltage: 'Tegangan Baterai',
  }[parameter] || String(parameter || '').replace(/_/g, ' ');
}

function httpJson(method, urlString, body = null, headers = {}) {
  const url = new URL(urlString);
  const payload = body ? Buffer.from(JSON.stringify(body)) : null;
  const client = url.protocol === 'https:' ? https : http;

  return new Promise((resolve, reject) => {
    const request = client.request({
      method,
      hostname: url.hostname,
      port: url.port || (url.protocol === 'https:' ? 443 : 80),
      path: `${url.pathname}${url.search}`,
      headers: {
        Accept: 'application/json',
        ...(payload ? { 'Content-Type': 'application/json', 'Content-Length': payload.length } : {}),
        ...headers,
      },
      timeout: numberEnv('REDNODE_HTTP_TIMEOUT_MS', 10000),
    }, (response) => {
      const chunks = [];

      response.on('data', (chunk) => chunks.push(chunk));
      response.on('end', () => {
        const text = Buffer.concat(chunks).toString('utf8');
        let parsed = {};

        try {
          parsed = text ? JSON.parse(text) : {};
        } catch (error) {
          reject(new Error(`Invalid JSON from ${urlString}: ${text.slice(0, 160)}`));
          return;
        }

        if (response.statusCode < 200 || response.statusCode >= 300 || parsed.ok === false) {
          reject(new Error(parsed.message || `HTTP ${response.statusCode} from ${urlString}`));
          return;
        }

        resolve(parsed);
      });
    });

    request.on('timeout', () => request.destroy(new Error(`Timeout calling ${urlString}`)));
    request.on('error', reject);

    if (payload) {
      request.write(payload);
    }

    request.end();
  });
}

async function fetchConfig() {
  const url = new URL(configUrl);
  url.searchParams.set('logger_code', loggerCode);

  const headers = configToken ? { Authorization: `Bearer ${configToken}` } : {};
  const data = await httpJson('GET', url.toString(), null, headers);

  if (!Array.isArray(data.sensors)) {
    throw new Error('RedNode config tidak memiliki daftar sensors.');
  }

  activeConfig = data;
  refreshSensorState(data.sensors);
  ensureMqtt(data);
  console.log(`[config] ${data.sensors.length} sensor loaded from ${url.toString()}`);
}

function refreshSensorState(sensors) {
  const nextCodes = new Set(sensors.map((sensor) => sensor.sensor_code));

  for (const code of sensorState.keys()) {
    if (!nextCodes.has(code)) {
      sensorState.delete(code);
    }
  }

  for (const sensor of sensors) {
    if (!sensorState.has(sensor.sensor_code)) {
      sensorState.set(sensor.sensor_code, { nextAt: 0, busy: false, lastValue: null, lastError: null });
    }
  }
}

function ensureMqtt(config) {
  const mqttConfig = config.mqtt || {};
  const enabled = String(process.env.REDNODE_MQTT_ENABLED || mqttConfig.enabled || '').toLowerCase() === 'true';
  const brokerUrl = process.env.REDNODE_MQTT_BROKER_URL || mqttConfig.broker_url;

  if (!enabled || !brokerUrl) {
    if (mqttClient) {
      mqttClient.end(true);
      mqttClient = null;
      mqttConnected = false;
      mqttBrokerUrl = null;
    }
    return;
  }

  if (mqttClient && mqttBrokerUrl === brokerUrl) {
    return;
  }

  if (mqttClient) {
    mqttClient.end(true);
  }

  mqttConnected = false;
  mqttBrokerUrl = brokerUrl;
  mqttClient = mqtt.connect(brokerUrl, {
    username: process.env.REDNODE_MQTT_USERNAME || mqttConfig.username || process.env.MQTT_USERNAME || undefined,
    password: process.env.REDNODE_MQTT_PASSWORD || process.env.MQTT_PASSWORD || undefined,
    reconnectPeriod: 2000,
    connectTimeout: numberEnv('REDNODE_MQTT_TIMEOUT_MS', 10000),
  });

  mqttClient.on('connect', () => {
    mqttConnected = true;
    console.log(`[mqtt] connected ${brokerUrl}`);
  });
  mqttClient.on('close', () => {
    mqttConnected = false;
  });
  mqttClient.on('error', (error) => {
    mqttConnected = false;
    console.error(`[mqtt] ${error.message}`);
  });
}

async function ensureSerial(serial) {
  const next = {
    port: serial.port || process.env.REDNODE_SERIAL_PORT || '/dev/ttyAS2',
    baudRate: Number(serial.baud_rate || process.env.REDNODE_BAUD_RATE || 9600),
    dataBits: Number(serial.data_bits || process.env.REDNODE_DATA_BITS || 8),
    stopBits: Number(serial.stop_bits || process.env.REDNODE_STOP_BITS || 1),
    parity: serial.parity || process.env.REDNODE_PARITY || 'none',
    timeout: Number(serial.timeout_ms || process.env.REDNODE_TIMEOUT_MS || 1500),
  };
  const key = JSON.stringify(next);

  if (activeSerialKey === key && modbus.isOpen) {
    modbus.setTimeout(next.timeout);
    return;
  }

  if (modbus.isOpen) {
    await new Promise((resolve) => modbus.close(resolve));
  }

  modbus = new ModbusRTU();
  modbus.setTimeout(next.timeout);
  await modbus.connectRTUBuffered(next.port, {
    baudRate: next.baudRate,
    dataBits: next.dataBits,
    stopBits: next.stopBits,
    parity: next.parity,
  });
  activeSerialKey = key;
  console.log(`[serial] opened ${next.port} ${next.baudRate} ${next.dataBits}${next.parity[0]?.toUpperCase() || 'N'}${next.stopBits}`);
}

async function readSensor(sensor) {
  const functionCode = normalizeFunctionCode(sensor.function_code);
  const address = Number(sensor.address || 0);
  const quantity = Math.max(Number(sensor.quantity || 1), sensor.data_type?.includes('32') ? 2 : 1);

  modbus.setID(Number(sensor.slave_id || 1));

  if (functionCode === 'FC01') {
    return (await modbus.readCoils(address, quantity)).data;
  }
  if (functionCode === 'FC02') {
    return (await modbus.readDiscreteInputs(address, quantity)).data;
  }
  if (functionCode === 'FC04') {
    return (await modbus.readInputRegisters(address, quantity)).data;
  }

  return (await modbus.readHoldingRegisters(address, quantity)).data;
}

function valueFromRegisters(registers, sensor) {
  const dataType = String(sensor.data_type || 'uint16').toLowerCase();

  if (dataType.includes('bool')) {
    return registers[0] ? 1 : 0;
  }

  if (dataType.includes('float') && registers.length >= 2) {
    const buffer = Buffer.allocUnsafe(4);
    buffer.writeUInt16BE(Number(registers[0]) & 0xffff, 0);
    buffer.writeUInt16BE(Number(registers[1]) & 0xffff, 2);
    const floatValue = buffer.readFloatBE(0);

    if (Number(registers[1]) === 0 && Math.abs(floatValue) < 0.000001 && Number(registers[0]) !== 0) {
      return Number(registers[0]);
    }

    return floatValue;
  }

  if (dataType.includes('int32') && registers.length >= 2) {
    const buffer = Buffer.allocUnsafe(4);
    buffer.writeUInt16BE(Number(registers[0]) & 0xffff, 0);
    buffer.writeUInt16BE(Number(registers[1]) & 0xffff, 2);
    return buffer.readInt32BE(0);
  }

  if (dataType.includes('uint32') && registers.length >= 2) {
    const buffer = Buffer.allocUnsafe(4);
    buffer.writeUInt16BE(Number(registers[0]) & 0xffff, 0);
    buffer.writeUInt16BE(Number(registers[1]) & 0xffff, 2);
    return buffer.readUInt32BE(0);
  }

  const raw = Number(registers[0] || 0);
  return dataType.includes('int16') && raw > 0x7fff ? raw - 0x10000 : raw;
}

function evaluate(sensor, registers) {
  const raw = valueFromRegisters(registers, sensor);
  const scale = Number(sensor.scale_factor ?? 1);
  const offset = Number(sensor.offset ?? 0);
  const value = (Number(raw) * (Number.isFinite(scale) ? scale : 1)) + (Number.isFinite(offset) ? offset : 0);
  const threshold = numericFromText(sensor.threshold || sensor.rule);
  const thresholdExceeded = threshold === null ? null : value > threshold;
  const parameterValues = weatherValuesFromRegisters(registers, sensor);

  return {
    raw,
    registers,
    value,
    value_text: parameterValues.length
      ? parameterValues.map((item) => `${item.label} ${item.value_text}`).join(', ')
      : `${Number(value).toFixed(2)}${unitSuffix(sensor)}`,
    parameter_values: parameterValues,
    threshold,
    threshold_exceeded: thresholdExceeded,
  };
}

function weatherValuesFromRegisters(registers, sensor) {
  const parameters = Array.isArray(sensor.weather_parameters) ? sensor.weather_parameters : [];

  if (sensor.sensor_type !== 'weather_station' && sensor.type !== 'weather_station') {
    return [];
  }

  if (!parameters.length) {
    return [];
  }

  const scale = Number(sensor.scale_factor ?? 1);
  const offset = Number(sensor.offset ?? 0);
  const safeScale = Number.isFinite(scale) ? scale : 1;
  const safeOffset = Number.isFinite(offset) ? offset : 0;

  return parameters.map((parameter, index) => {
    const raw = Number(registers[index] || 0);
    const value = (raw * safeScale) + safeOffset;

    return {
      parameter,
      label: weatherParameterLabel(parameter),
      raw,
      value,
      value_text: `${Number(value).toFixed(2)}${unitSuffix(sensor)}`,
    };
  });
}

async function postTelemetry(sensor, result) {
  const callbackUrl = process.env.REDNODE_CALLBACK_URL || activeConfig?.callback?.url;

  if (!callbackUrl) {
    return;
  }

  const observedAt = new Date().toISOString();
  const registers = Array.isArray(result.registers)
    ? result.registers.map((value) => Number(value) & 0xffff)
    : [];
  const body = {
    event_id: stableId('rednode', {
      loggerCode,
      sensor: sensor.sensor_id || sensor.sensor_code,
      observedAt,
      registers,
      address: Number(sensor.address || 0),
      functionCode: normalizeFunctionCode(sensor.function_code),
    }),
    envelope_version: '1',
    transport: 'rednode',
    payload_classification: Array.isArray(result.registers) && result.registers.length ? 'raw' : 'pre_normalized',
    observed_at: observedAt,
    sensor_id: sensor.sensor_id,
    data_logger_id: activeConfig?.logger?.id || null,
    logger_code: activeConfig?.logger?.logger_code || loggerCode,
    raw: result.raw,
    registers,
    register_address: Number(sensor.address || 0),
    function_code: normalizeFunctionCode(sensor.function_code),
    value: result.value_text,
    parameter_values: result.parameter_values || [],
    ...(result.threshold_exceeded !== null ? { threshold_exceeded: result.threshold_exceeded } : {}),
  };
  const headers = callbackToken ? { Authorization: `Bearer ${callbackToken}` } : {};

  await httpJson('POST', callbackUrl, body, headers);
  console.log(`[callback] ${sensor.sensor_code} terkirim ke ${callbackUrl}`);
}

async function postHeartbeat(connected, lastError) {
  const heartbeatUrl = process.env.REDNODE_HEARTBEAT_URL || activeConfig?.connection_report?.url || activeConfig?.heartbeat?.url;

  if (!heartbeatUrl || (!pendingHeartbeatReport && Date.now() - lastHeartbeatAt < heartbeatMs)) {
    return;
  }

  const serial = activeConfig?.serial || {};
  const headers = callbackToken ? { Authorization: `Bearer ${callbackToken}` } : {};

  if (!pendingHeartbeatReport) {
    const reportId = crypto.randomUUID();
    pendingHeartbeatReport = {
      report_id: reportId,
      logger_code: loggerCode,
      serial_port: serial.port,
      pin_mapping: serial.pin_mapping || '',
      connected,
      last_error: lastError || null,
      sensors: [],
    };
  }

  await httpJson('POST', heartbeatUrl, pendingHeartbeatReport, headers);

  pendingHeartbeatReport = null;
  lastHeartbeatAt = Date.now();
}

function publishTelemetry(sensor, result) {
  if (!mqttClient || !mqttConnected) {
    return;
  }

  const topicPrefix = process.env.REDNODE_MQTT_TOPIC_PREFIX || activeConfig?.mqtt?.topic_prefix || 'resq/telemetry';
  const topic = `${topicPrefix.replace(/\/$/, '')}/${sensor.sensor_code}`;
  const payload = JSON.stringify({
    sensor_id: sensor.sensor_id,
    sensor_code: sensor.sensor_code,
    sensor_label: sensor.sensor_label,
    sensor_type: sensor.sensor_type || sensor.type,
    parameter: sensor.parameter,
    weather_parameters: sensor.weather_parameters || [],
    data_logger_id: activeConfig?.logger?.id || null,
    value: result.value,
    value_text: result.value_text,
    parameter_values: result.parameter_values || [],
    raw: result.raw,
    registers: result.registers,
    threshold: result.threshold,
    threshold_exceeded: result.threshold_exceeded,
    received_at: new Date().toISOString(),
  });

  mqttClient.publish(topic, payload, { qos: 0, retain: false });
}

async function pollDueSensors() {
  if (!activeConfig || running) {
    return;
  }

  running = true;
  let connected = false;
  let heartbeatError = null;

  try {
    await ensureSerial(activeConfig.serial || {});
    connected = true;

    const now = Date.now();
    for (const sensor of activeConfig.sensors) {
      const state = sensorState.get(sensor.sensor_code) || { nextAt: 0 };
      if (state.nextAt > now) {
        continue;
      }

      try {
        const registers = await readSensor(sensor);
        const result = evaluate(sensor, registers);
        await postTelemetry(sensor, result);
        publishTelemetry(sensor, result);

        state.lastValue = result.value_text;
        state.lastError = null;
        console.log(
          `[sensor] ${sensor.sensor_code} = ${result.value_text} ` +
          `| raw=${result.raw} | registers=[${registers.join(', ')}]`
        );
      } catch (error) {
        state.lastError = error.message;
        heartbeatError = error.message;
        console.error(`[sensor] ${sensor.sensor_code}: ${error.message}`);
      } finally {
        state.nextAt = Date.now() + Math.max(Number(sensor.poll_interval_ms || 1000), 250);
        sensorState.set(sensor.sensor_code, state);
      }
    }
  } catch (error) {
    connected = false;
    heartbeatError = error.message;
    console.error(`[serial] ${error.message}`);
  } finally {
    postHeartbeat(connected, heartbeatError).catch((error) => {
      console.error(`[laporan-koneksi] ${error.message}`);
    });
    running = false;
  }
}

async function main() {
  console.log(`[rednode] config ${configUrl}`);
  fetchConfig().catch((error) => {
    console.error(`[config] ${error.message}`);
    console.error('[config] menunggu server web bisa diakses, gateway tetap hidup dan akan retry otomatis');
  });

  setInterval(() => {
    fetchConfig().catch((error) => console.error(`[config] ${error.message}`));
  }, configRefreshMs);

  setInterval(() => {
    pollDueSensors().catch((error) => console.error(`[poll] ${error.message}`));
  }, loopTickMs);
}

process.on('SIGINT', async () => {
  if (mqttClient) {
    mqttClient.end(true);
  }
  if (modbus.isOpen) {
    await new Promise((resolve) => modbus.close(resolve));
  }
  process.exit(0);
});

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
