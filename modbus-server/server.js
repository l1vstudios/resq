const crypto = require('crypto');
const mqttDeliveryCache = new Map();

if (process.argv.includes('--verify-mqtt-delivery-identity')) {
  const verification = verifyMqttDeliveryIdentity();
  console.log(JSON.stringify(verification, null, 2));
  process.exit(verification.failed === 0 ? 0 : 1);
}

const cors = require('cors');
require('dotenv').config({ quiet: true });
const express = require('express');
const mqtt = require('mqtt');
const ModbusRTU = require('modbus-serial');

const app = express();
const port = Number(process.env.MODBUS_BACKEND_PORT || 3100);
const allowedOrigin = corsOriginFromEnv(process.env.MODBUS_CORS_ORIGIN);
const mqttAutostart = String(process.env.MQTT_AUTOSTART || '').toLowerCase() === 'true';

let client = new ModbusRTU();
let connection = null;
let stats = {
  tx: 0,
  rx: 0,
  err: 0,
  lastUpdate: null,
  lastError: null,
};
let pollJob = {
  active: false,
  timer: null,
  payload: null,
  lastResult: null,
  lastError: null,
  startedAt: null,
};
let mqttState = {
  active: false,
  client: null,
  config: null,
  connected: false,
  lastMessage: null,
  log: [],
  lastError: null,
  startedAt: null,
};
app.use(cors({ origin: allowedOrigin }));
app.use(express.json({ limit: '64kb' }));

function corsOriginFromEnv(value) {
  const normalized = String(value || '').trim().toLowerCase();
  if (value === undefined || value === null || normalized === '' || normalized === '*' || normalized === 'true') {
    return true;
  }

  if (normalized === 'false') {
    return false;
  }

  if (String(value).includes(',')) {
    return String(value).split(',').map((item) => item.trim()).filter(Boolean);
  }

  return value;
}

function toInteger(value, fallback) {
  if (value === undefined || value === null || value === '') {
    return fallback;
  }

  const parsed = Number(value);
  return Number.isFinite(parsed) ? Math.trunc(parsed) : fallback;
}

function normalizeFunctionCode(value, fallback = 'FC03') {
  const normalized = String(value || fallback).toUpperCase().replace(/[^0-9]/g, '');
  return `FC${normalized.padStart(2, '0')}`;
}

function validateRange(name, value, min, max) {
  if (!Number.isInteger(value) || value < min || value > max) {
    const error = new Error(`${name} must be an integer between ${min} and ${max}.`);
    error.status = 422;
    throw error;
  }
}

function connectionPayload(payload) {
  const host = String(payload.host || '').trim();
  const tcpPort = toInteger(payload.port, 502);
  const unitId = toInteger(payload.unitId ?? payload.slaveId, 1);
  const timeout = toInteger(payload.timeout, 1000);

  if (!host) {
    const error = new Error('host is required.');
    error.status = 422;
    throw error;
  }

  validateRange('port', tcpPort, 1, 65535);
  validateRange('unitId', unitId, 1, 247);
  validateRange('timeout', timeout, 100, 60000);

  return { host, port: tcpPort, unitId, timeout };
}

function sameConnection(next) {
  return connection
    && client.isOpen
    && connection.host === next.host
    && connection.port === next.port
    && connection.unitId === next.unitId;
}

async function closeClient() {
  if (!client.isOpen) {
    connection = null;
    return;
  }

  await new Promise((resolve) => {
    client.close(() => resolve());
  });

  connection = null;
}

async function ensureConnection(payload) {
  const next = connectionPayload(payload);

  if (sameConnection(next)) {
    client.setTimeout(next.timeout);
    connection = { ...connection, timeout: next.timeout };
    return connection;
  }

  await closeClient();
  client = new ModbusRTU();
  client.setID(next.unitId);
  client.setTimeout(next.timeout);
  await client.connectTCP(next.host, { port: next.port });
  connection = { ...next, connectedAt: new Date().toISOString() };

  return connection;
}

function registerRows(registers, startAddress) {
  return registers.map((value, index) => {
    const uint16 = value & 0xffff;
    const int16 = uint16 > 0x7fff ? uint16 - 0x10000 : uint16;

    return {
      address: startAddress + index,
      raw: uint16,
      uint16,
      int16,
      hex: `0x${uint16.toString(16).toUpperCase().padStart(4, '0')}`,
      binary: uint16.toString(2).padStart(16, '0').replace(/(.{8})/g, '$1 ').trim(),
    };
  });
}

function addFloat32(rows, registers) {
  for (let index = 0; index < rows.length; index += 1) {
    if (index + 1 >= registers.length) {
      rows[index].float32 = null;
      continue;
    }

    const buffer = Buffer.allocUnsafe(4);
    buffer.writeUInt16BE(registers[index] & 0xffff, 0);
    buffer.writeUInt16BE(registers[index + 1] & 0xffff, 2);
    rows[index].float32 = Number(buffer.readFloatBE(0).toFixed(6));
  }
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

function booleanFromValue(value) {
  if (value === true || value === false) {
    return value;
  }

  if (value === undefined || value === null || value === '') {
    return null;
  }

  const normalized = String(value).trim().toLowerCase();
  if (['1', 'true', 'yes', 'on', 'awas', 'danger'].includes(normalized)) {
    return true;
  }
  if (['0', 'false', 'no', 'off', 'normal'].includes(normalized)) {
    return false;
  }

  return null;
}

function stableEventId(prefix, facts) {
  const digest = crypto.createHash('sha256').update(JSON.stringify(facts)).digest('hex');
  return `${prefix}-${digest}`;
}

function thresholdNumber(sensor) {
  return numericFromText(sensor?.threshold || sensor?.rule);
}

function sensorUnit(sensor) {
  const unit = String(sensor?.unit || '').trim();
  return unit && unit !== '0' ? ` ${unit}` : '';
}

function sensorValueFromRow(row, sensor) {
  if (!row) {
    return null;
  }

  const dataType = String(sensor?.data_type || '').toLowerCase();
  let rawValue = row.uint16 ?? row.raw;

  if (dataType.includes('float') && row.float32 !== null && row.float32 !== undefined) {
    rawValue = row.float32;
  } else if (dataType.includes('int16')) {
    rawValue = row.int16;
  } else if (dataType.includes('bool')) {
    rawValue = row.raw ? 1 : 0;
  }

  const numeric = Number(rawValue);
  if (!Number.isFinite(numeric)) {
    return null;
  }

  const scale = Number(sensor?.scale_factor ?? 1);
  const offset = Number(sensor?.offset ?? 0);

  return (numeric * (Number.isFinite(scale) ? scale : 1)) + (Number.isFinite(offset) ? offset : 0);
}

function sensorValueFromMqtt(message, sensor) {
  let parsed = message;

  try {
    parsed = JSON.parse(message);
  } catch (error) {
    parsed = message;
  }

  if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
    const preferredKeys = ['value', 'reading', 'data', 'payload'];
    const key = preferredKeys.find((item) => parsed[item] !== undefined);
    parsed = key ? parsed[key] : Object.values(parsed)[0];
  }

  const numeric = numericFromText(parsed);
  if (numeric === null) {
    return null;
  }

  const scale = Number(sensor?.scale_factor ?? 1);
  const offset = Number(sensor?.offset ?? 0);

  return (numeric * (Number.isFinite(scale) ? scale : 1)) + (Number.isFinite(offset) ? offset : 0);
}

function evaluateSensorValue(value, sensor) {
  if (value === null || value === undefined || !sensor) {
    return null;
  }

  const threshold = thresholdNumber(sensor);
  const unit = sensorUnit(sensor);
  const numeric = Number(value);

  if (!Number.isFinite(numeric)) {
    return null;
  }

  return {
    value: numeric,
    valueText: `${numeric.toFixed(2)}${unit}`,
    threshold,
    thresholdExceeded: threshold !== null ? numeric > threshold : null,
  };
}

function pushMqttLog(entry) {
  mqttState.log = [
    {
      ...entry,
      receivedAt: entry.receivedAt || new Date().toISOString(),
    },
    ...(mqttState.log || []),
  ].slice(0, 50);
}

async function postSensorUpdate(callback, sensor, evaluation, evidence = {}) {
  if (!callback?.url || !evaluation) {
    return;
  }

  const sensorId = sensor?.db_id || sensor?.sensor_id || evaluation.sensor_id;
  const sensorCode = sensor?.code || sensor?.sensor_code || evaluation.sensor_code;
  if (!sensorId && !sensorCode) {
    return;
  }

  const headers = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  if (callback.token) {
    headers.Authorization = `Bearer ${callback.token}`;
  }

  const observedAt = evidence.observedAt || new Date().toISOString();
  const registers = Array.isArray(evidence.registers) ? evidence.registers : [];
  const rawPayload = evidence.rawPayload;
  const response = await fetch(callback.url, {
    method: 'POST',
    headers,
    body: JSON.stringify({
      event_id: evidence.eventId || stableEventId('gateway', {
        transport: evidence.transport,
        sensorId,
        sensorCode,
        observedAt,
        registers,
        rawPayload,
      }),
      envelope_version: '1',
      transport: evidence.transport || 'modbus_tcp',
      payload_classification: evidence.payloadClassification
        || (registers.length ? 'raw' : 'pre_normalized'),
      observed_at: observedAt,
      ...(sensorId ? { sensor_id: sensorId } : {}),
      ...(sensorCode && !sensorId ? { sensor_code: sensorCode } : {}),
      ...(evaluation.data_logger_id ? { data_logger_id: evaluation.data_logger_id } : {}),
      ...(registers.length ? { registers } : {}),
      ...(evidence.registerAddress !== undefined ? { register_address: evidence.registerAddress } : {}),
      ...(evidence.functionCode ? { function_code: evidence.functionCode } : {}),
      ...(rawPayload !== undefined ? { raw_payload: rawPayload } : {}),
      value: evaluation.valueText,
      ...(evaluation.thresholdExceeded !== null && evaluation.thresholdExceeded !== undefined
        ? { threshold_exceeded: evaluation.thresholdExceeded }
        : {}),
    }),
  });

  if (!response.ok) {
    const body = await response.text();
    throw new Error(`Laravel callback failed (${response.status}): ${body.slice(0, 160)}`);
  }
}

async function readModbus(payload) {
  const address = toInteger(payload.address, 0);
  const quantity = toInteger(payload.quantity, 1);
  const functionCode = normalizeFunctionCode(payload.functionCode, 'FC03');

  validateRange('address', address, 0, 65535);
  validateRange('quantity', quantity, 1, functionCode === 'FC15' ? 1968 : 125);

  await ensureConnection(payload);
  stats.tx += 1;

  let result;
  if (functionCode === 'FC01') {
    result = await client.readCoils(address, quantity);
  } else if (functionCode === 'FC02') {
    result = await client.readDiscreteInputs(address, quantity);
  } else if (functionCode === 'FC03') {
    result = await client.readHoldingRegisters(address, quantity);
  } else if (functionCode === 'FC04') {
    result = await client.readInputRegisters(address, quantity);
  } else {
    const error = new Error('Supported read function codes are FC01, FC02, FC03, and FC04.');
    error.status = 422;
    throw error;
  }

  stats.rx += 1;
  stats.lastUpdate = new Date().toISOString();
  stats.lastError = null;

  const data = Array.isArray(result.data) ? result.data : [];
  const rows = functionCode === 'FC01' || functionCode === 'FC02'
    ? data.map((value, index) => ({
      address: address + index,
      raw: Boolean(value),
      uint16: value ? 1 : 0,
      int16: value ? 1 : 0,
      hex: value ? '0x0001' : '0x0000',
      binary: value ? '00000000 00000001' : '00000000 00000000',
      float32: null,
    }))
    : registerRows(data, address);

  if (functionCode === 'FC03' || functionCode === 'FC04') {
    addFloat32(rows, data);
  }

  return {
    ok: true,
    connection,
    functionCode,
    address,
    quantity,
    rows,
    stats,
  };
}

async function writeModbus(payload) {
  const address = toInteger(payload.address, 0);
  const functionCode = normalizeFunctionCode(payload.functionCode, 'FC06');

  validateRange('address', address, 0, 65535);
  await ensureConnection(payload);
  stats.tx += 1;

  if (functionCode === 'FC05') {
    await client.writeCoil(address, Boolean(payload.value));
  } else if (functionCode === 'FC06') {
    const value = toInteger(payload.value, 0);
    validateRange('value', value, 0, 65535);
    await client.writeRegister(address, value);
  } else if (functionCode === 'FC15') {
    const values = Array.isArray(payload.values) ? payload.values.map(Boolean) : [];
    if (!values.length) {
      const error = new Error('values must contain at least one coil value.');
      error.status = 422;
      throw error;
    }
    await client.writeCoils(address, values);
  } else if (functionCode === 'FC16') {
    const values = Array.isArray(payload.values)
      ? payload.values.map((value) => toInteger(value, 0))
      : [toInteger(payload.value, 0)];
    values.forEach((value) => validateRange('value', value, 0, 65535));
    await client.writeRegisters(address, values);
  } else {
    const error = new Error('Supported write function codes are FC05, FC06, FC15, and FC16.');
    error.status = 422;
    throw error;
  }

  stats.rx += 1;
  stats.lastUpdate = new Date().toISOString();
  stats.lastError = null;

  return {
    ok: true,
    connection,
    functionCode,
    address,
    stats,
  };
}

async function runPollOnce() {
  if (!pollJob.active || !pollJob.payload) {
    return null;
  }

  const result = await readModbus(pollJob.payload);
  const sensor = pollJob.payload.sensor;
  const evaluation = evaluateSensorValue(sensorValueFromRow(result.rows?.[0], sensor), sensor);

  await postSensorUpdate(pollJob.payload.callback, sensor, evaluation, {
    eventId: stableEventId('modbus', {
      sensor: sensor?.db_id || sensor?.sensor_id || sensor?.code || sensor?.sensor_code,
      observedAt: result.stats?.lastUpdate,
      registers: (result.rows || []).map((row) => row.uint16),
      registerAddress: result.address,
      functionCode: result.functionCode,
    }),
    observedAt: result.stats?.lastUpdate || new Date().toISOString(),
    transport: 'modbus_tcp',
    registers: (result.rows || []).map((row) => row.uint16),
    registerAddress: result.address,
    functionCode: result.functionCode,
  });

  pollJob.lastResult = {
    ...result,
    evaluation,
    updatedAt: new Date().toISOString(),
  };
  pollJob.lastError = null;

  return pollJob.lastResult;
}

function stopPollJob() {
  clearInterval(pollJob.timer);
  pollJob = {
    active: false,
    timer: null,
    payload: null,
    lastResult: pollJob.lastResult,
    lastError: pollJob.lastError,
    startedAt: null,
  };
}

function startPollJob(payload) {
  stopPollJob();

  const interval = Math.max(toInteger(payload.interval ?? payload.pollInterval, 1000), 250);
  pollJob = {
    active: true,
    timer: null,
    payload: { ...payload, interval },
    lastResult: null,
    lastError: null,
    startedAt: new Date().toISOString(),
  };

  runPollOnce().catch((error) => {
    pollJob.lastError = error.message;
    stats.err += 1;
    stats.lastError = error.message;
  });

  pollJob.timer = setInterval(() => {
    runPollOnce().catch((error) => {
      pollJob.lastError = error.message;
      stats.err += 1;
      stats.lastError = error.message;
    });
  }, interval);

  return pollJob;
}

function mqttStatus() {
  return {
    active: mqttState.active,
    connected: mqttState.connected,
    config: mqttState.config
      ? {
        brokerUrl: mqttState.config.brokerUrl,
        topic: mqttState.config.topic,
        sensor: mqttState.config.sensor,
      }
      : null,
    lastMessage: mqttState.lastMessage,
    log: mqttState.log || [],
    lastError: mqttState.lastError,
    startedAt: mqttState.startedAt,
  };
}

function mqttCallbackFromEnv() {
  const callbackUrl = process.env.MQTT_CALLBACK_URL
    || process.env.LARAVEL_CALLBACK_URL
    || (process.env.APP_URL ? `${process.env.APP_URL.replace(/\/$/, '')}/api/realtime-sensor-status` : null);

  return {
    url: callbackUrl,
    token: process.env.MQTT_CALLBACK_TOKEN || '',
  };
}

function mqttPayloadFromEnv() {
  return {
    brokerUrl: process.env.MQTT_BROKER_URL,
    topic: process.env.MQTT_TOPIC || 'resq/telemetry/#',
    username: process.env.MQTT_USERNAME || undefined,
    password: process.env.MQTT_PASSWORD || undefined,
    timeout: toInteger(process.env.MQTT_CONNECT_TIMEOUT_MS, 10000),
    sensor: process.env.MQTT_SENSOR_CODE
      ? { sensor_code: process.env.MQTT_SENSOR_CODE, code: process.env.MQTT_SENSOR_CODE }
      : null,
    callback: mqttCallbackFromEnv(),
  };
}

function valueFromPayload(parsed) {
  if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
    return parsed;
  }

  const preferredKeys = ['value', 'reading', 'data', 'payload'];
  const key = preferredKeys.find((item) => parsed[item] !== undefined);

  return key ? parsed[key] : Object.values(parsed)[0];
}

function readingFromMqtt(message, incomingTopic, configuredSensor) {
  let parsed = message;

  try {
    parsed = JSON.parse(message);
  } catch (error) {
    parsed = message;
  }

  const objectPayload = parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
  const topicParts = String(incomingTopic || '').split('/').filter(Boolean);
  const topicSensorCode = topicParts.length ? topicParts[topicParts.length - 1] : null;
  const sensorId = configuredSensor?.db_id
    || objectPayload.sensor_id
    || objectPayload.sensor_db_id
    || objectPayload.db_id
    || null;
  const sensorCode = configuredSensor?.sensor_code
    || configuredSensor?.code
    || objectPayload.sensor_code
    || objectPayload.sensorCode
    || objectPayload.code
    || topicSensorCode;
  const rawValue = valueFromPayload(parsed);
  const numericValue = numericFromText(rawValue);
  const thresholdExceeded = booleanFromValue(
    objectPayload.threshold_exceeded ?? objectPayload.thresholdExceeded ?? objectPayload.alert
  );
  const unit = sensorUnit(configuredSensor);

  return {
    sensor_id: sensorId,
    sensor_code: sensorCode,
    data_logger_id: objectPayload.data_logger_id || objectPayload.dataLoggerId || null,
    rawValue,
    value: numericValue,
    valueText: numericValue === null ? String(rawValue ?? '') : `${numericValue.toFixed(2)}${unit}`,
    thresholdExceeded,
  };
}

function mqttDeliveryIdentity(config, incomingTopic, buffer, packet, clientId) {
  let objectPayload = {};
  try {
    const parsed = JSON.parse(buffer.toString());
    objectPayload = parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
  } catch (error) {
    objectPayload = {};
  }

  const sourceEventId = publisherEventId(objectPayload);
  const sourceObservedAt = objectPayload.observed_at || objectPayload.observedAt
    || objectPayload.received_at || objectPayload.receivedAt
    || objectPayload.timestamp || null;
  const payloadSha256 = crypto.createHash('sha256').update(buffer).digest('hex');
  const now = Date.now();
  const parsedSourceTime = sourceObservedAt === null ? NaN : Date.parse(String(sourceObservedAt));
  const observedAt = Number.isFinite(parsedSourceTime)
    ? new Date(parsedSourceTime).toISOString()
    : new Date(now).toISOString();

  if (sourceEventId !== null) {
    const facts = {
      brokerUrl: config?.brokerUrl || null,
      topic: incomingTopic,
      sourceEventId,
      sourceObservedAt: sourceObservedAt === null ? null : String(sourceObservedAt),
      payloadSha256,
    };

    return {
      eventId: stableEventId('mqtt', facts),
      observedAt,
    };
  }

  const qos = toInteger(packet?.qos, 0);
  const messageId = toInteger(packet?.messageId, null);
  const identity = {
    eventId: `mqtt-${crypto.randomUUID()}`,
    observedAt,
  };

  if (qos < 1 || messageId === null) {
    return identity;
  }

  const cacheKey = stableEventId('mqtt-session-delivery', {
    brokerUrl: config?.brokerUrl || null,
    clientId: clientId || null,
    topic: incomingTopic,
    messageId,
    qos,
  });
  const ttlMs = Math.min(Math.max(toInteger(process.env.MQTT_DEDUP_WINDOW_MS, 60000), 1000), 3600000);

  for (const [key, cached] of mqttDeliveryCache) {
    if (cached.expiresAt <= now) {
      mqttDeliveryCache.delete(key);
    }
  }

  const cached = mqttDeliveryCache.get(cacheKey);
  if (packet?.dup === true && cached?.payloadSha256 === payloadSha256) {
    return cached.identity;
  }

  while (mqttDeliveryCache.size >= 1000) {
    mqttDeliveryCache.delete(mqttDeliveryCache.keys().next().value);
  }

  mqttDeliveryCache.set(cacheKey, { identity, payloadSha256, expiresAt: now + ttlMs });

  return identity;
}

function publisherEventId(payload) {
  const candidate = payload.event_id ?? payload.eventId
    ?? payload.message_id ?? payload.messageId ?? null;

  if ((typeof candidate !== 'string' && typeof candidate !== 'number') || String(candidate).trim() === '') {
    return null;
  }

  return String(candidate).trim();
}

function verifyMqttDeliveryIdentity() {
  const config = { brokerUrl: 'mqtt://verification.invalid' };
  const topic = 'resq/telemetry/verification';
  const clientId = 'verification-client';
  const payload = Buffer.from('{"value":30.2}');

  mqttDeliveryCache.clear();
  const qos0First = mqttDeliveryIdentity(config, topic, payload, { qos: 0 }, clientId);
  const qos0Second = mqttDeliveryIdentity(config, topic, payload, { qos: 0 }, clientId);
  const qos1First = mqttDeliveryIdentity(config, topic, payload, { qos: 1, messageId: 17, dup: false }, clientId);
  const qos1Duplicate = mqttDeliveryIdentity(config, topic, payload, { qos: 1, messageId: 17, dup: true }, clientId);
  const qos1ReusedPacketId = mqttDeliveryIdentity(config, topic, payload, { qos: 1, messageId: 17, dup: false }, clientId);
  const publisherPayload = Buffer.from('{"event_id":"publisher-17","value":30.2}');
  const publisherFirst = mqttDeliveryIdentity(config, topic, publisherPayload, { qos: 0 }, clientId);
  const publisherRetry = mqttDeliveryIdentity(config, topic, publisherPayload, { qos: 0 }, clientId);
  const assertions = {
    identical_qos0_deliveries_are_distinct: qos0First.eventId !== qos0Second.eventId,
    qos1_dup_redelivery_reuses_active_session_id: qos1First.eventId === qos1Duplicate.eventId,
    qos1_non_dup_packet_id_reuse_is_distinct: qos1First.eventId !== qos1ReusedPacketId.eventId,
    publisher_event_id_is_stable: publisherFirst.eventId === publisherRetry.eventId,
  };

  mqttDeliveryCache.clear();

  return {
    suite: 'mqtt-delivery-identity/1.0.0',
    passed: Object.values(assertions).filter(Boolean).length,
    failed: Object.values(assertions).filter((passed) => !passed).length,
    assertions,
  };
}

function mqttTopicForSensor(topicPattern, sensor) {
  const code = String(sensor?.code || sensor?.sensor_code || '').trim();
  const pattern = String(topicPattern || '').trim();

  if (!code) {
    const error = new Error('sensor code is required for MQTT broker test.');
    error.status = 422;
    throw error;
  }

  if (!pattern) {
    return `resq/telemetry/${code}`;
  }

  if (pattern.includes('#')) {
    return pattern.replace('#', code).replace(/\/+/g, '/');
  }

  if (pattern.includes('+')) {
    return pattern.replace('+', code).replace(/\/+/g, '/');
  }

  return `${pattern.replace(/\/$/, '')}/${code}`;
}

function mqttTestPayload(payload) {
  const sensor = payload.sensor || {};
  const rawValue = payload.value ?? '12.4';
  const numeric = numericFromText(rawValue);

  return {
    sensor_code: sensor.code || sensor.sensor_code,
    value: numeric === null ? String(rawValue) : numeric,
  };
}

async function publishMqttTest(payload) {
  if (!mqttState.client || !mqttState.connected) {
    const error = new Error('MQTT gateway is not connected. Connect MQTT first.');
    error.status = 409;
    throw error;
  }

  const topic = mqttTopicForSensor(payload.topic || mqttState.config?.topic, payload.sensor);
  const message = JSON.stringify(mqttTestPayload(payload));
  const qos = toInteger(payload.qos, 0);

  await new Promise((resolve, reject) => {
    mqttState.client.publish(topic, message, { qos, retain: false }, (error) => {
      if (error) {
        reject(error);
        return;
      }

      resolve();
    });
  });

  stats.tx += 1;
  stats.lastUpdate = new Date().toISOString();
  stats.lastError = null;

  return {
    ok: true,
    topic,
    payload: message,
    stats,
    mqtt: mqttStatus(),
  };
}

function stopMqtt() {
  if (mqttState.client) {
    mqttState.client.end(true);
  }

  mqttState = {
    active: false,
    client: null,
    config: null,
    connected: false,
    lastMessage: mqttState.lastMessage,
    log: mqttState.log || [],
    lastError: mqttState.lastError,
    startedAt: null,
  };
  mqttDeliveryCache.clear();
}

function startMqtt(payload) {
  const brokerUrl = String(payload.brokerUrl || '').trim();
  const topic = String(payload.topic || '').trim();

  if (!brokerUrl || !topic) {
    const error = new Error('brokerUrl and topic are required.');
    error.status = 422;
    throw error;
  }

  stopMqtt();

  const config = {
    brokerUrl,
    topic,
    username: payload.username || undefined,
    password: payload.password || undefined,
    sensor: payload.sensor,
    callback: payload.callback,
  };
  const mqttClient = mqtt.connect(brokerUrl, {
    username: config.username,
    password: config.password,
    reconnectPeriod: 2000,
    connectTimeout: toInteger(payload.timeout, 10000),
  });

  mqttState = {
    active: true,
    client: mqttClient,
    config,
    connected: false,
    lastMessage: null,
    log: mqttState.log || [],
    lastError: null,
    startedAt: new Date().toISOString(),
  };

  mqttClient.on('connect', (connack) => {
    if (!connack?.sessionPresent) {
      mqttDeliveryCache.clear();
    }
    mqttState.connected = true;
    mqttState.lastError = null;
    mqttClient.subscribe(topic, (error) => {
      if (error) {
        mqttState.lastError = error.message;
      }
    });
  });

  mqttClient.on('reconnect', () => {
    mqttState.connected = false;
  });

  mqttClient.on('close', () => {
    mqttState.connected = false;
  });

  mqttClient.on('error', (error) => {
    mqttState.lastError = error.message;
    stats.err += 1;
    stats.lastError = error.message;
  });

  mqttClient.on('message', (incomingTopic, buffer, packet) => {
    const message = buffer.toString();
    const sensor = mqttState.config?.sensor;
    const reading = readingFromMqtt(message, incomingTopic, sensor);
    const sensorEvaluation = evaluateSensorValue(sensorValueFromMqtt(message, sensor), sensor);
    const evaluation = sensorEvaluation
      ? {
        ...reading,
        value: sensorEvaluation.value,
        valueText: sensorEvaluation.valueText,
        threshold: sensorEvaluation.threshold,
        thresholdExceeded: sensorEvaluation.thresholdExceeded,
      }
      : reading;

    const receivedAt = new Date().toISOString();
    const deliveryIdentity = mqttDeliveryIdentity(
      mqttState.config,
      incomingTopic,
      buffer,
      packet,
      mqttClient.options?.clientId,
    );
    mqttState.lastMessage = {
      topic: incomingTopic,
      payload: message,
      evaluation,
      receivedAt,
    };
    pushMqttLog(mqttState.lastMessage);

    postSensorUpdate(mqttState.config?.callback, sensor, evaluation, {
      eventId: deliveryIdentity.eventId,
      observedAt: deliveryIdentity.observedAt,
      transport: 'mqtt',
      rawPayload: message,
      payloadClassification: 'pre_normalized',
    }).catch((error) => {
      mqttState.lastError = error.message;
      stats.err += 1;
      stats.lastError = error.message;
    });
  });

  return mqttStatus();
}

app.get('/health', (req, res) => {
  res.json({
    ok: true,
    connected: Boolean(client.isOpen && connection),
    connection,
    stats,
    pollJob: {
      active: pollJob.active,
      startedAt: pollJob.startedAt,
      lastResult: pollJob.lastResult,
      lastError: pollJob.lastError,
    },
    mqtt: mqttStatus(),
  });
});

app.post('/api/modbus/connect', async (req, res, next) => {
  try {
    await ensureConnection(req.body);
    res.json({
      ok: true,
      connected: true,
      connection,
      stats,
    });
  } catch (error) {
    next(error);
  }
});

app.post('/api/modbus/disconnect', async (req, res, next) => {
  try {
    await closeClient();
    res.json({
      ok: true,
      connected: false,
      connection,
      stats,
    });
  } catch (error) {
    next(error);
  }
});

app.post('/api/modbus/read', async (req, res, next) => {
  try {
    res.json(await readModbus(req.body));
  } catch (error) {
    next(error);
  }
});

app.post('/api/modbus/write', async (req, res, next) => {
  try {
    res.json(await writeModbus(req.body));
  } catch (error) {
    next(error);
  }
});

app.get('/api/poll/status', (req, res) => {
  res.json({
    ok: true,
    active: pollJob.active,
    startedAt: pollJob.startedAt,
    lastResult: pollJob.lastResult,
    lastError: pollJob.lastError,
    stats,
  });
});

app.post('/api/poll/start', (req, res, next) => {
  try {
    startPollJob(req.body);
    res.json({
      ok: true,
      active: pollJob.active,
      startedAt: pollJob.startedAt,
      stats,
    });
  } catch (error) {
    next(error);
  }
});

app.post('/api/poll/stop', (req, res) => {
  stopPollJob();
  res.json({
    ok: true,
    active: false,
    stats,
  });
});

app.get('/api/mqtt/status', (req, res) => {
  res.json({
    ok: true,
    mqtt: mqttStatus(),
    stats,
  });
});

app.post('/api/mqtt/connect', (req, res, next) => {
  try {
    res.json({
      ok: true,
      mqtt: startMqtt(req.body),
      stats,
    });
  } catch (error) {
    next(error);
  }
});

app.post('/api/mqtt/disconnect', (req, res) => {
  stopMqtt();
  res.json({
    ok: true,
    mqtt: mqttStatus(),
    stats,
  });
});

app.post('/api/mqtt/test-publish', async (req, res, next) => {
  try {
    res.json(await publishMqttTest(req.body));
  } catch (error) {
    next(error);
  }
});

app.use((error, req, res, next) => {
  stats.err += 1;
  stats.lastError = error.message;

  res.status(error.status || 500).json({
    ok: false,
    message: error.message,
    stats,
  });
});

app.listen(port, () => {
  console.log(`Modbus/MQTT gateway listening on port ${port}`);

  if (mqttAutostart) {
    try {
      startMqtt(mqttPayloadFromEnv());
      console.log(`MQTT autostart subscribed to ${process.env.MQTT_TOPIC || 'resq/telemetry/#'}`);
    } catch (error) {
      stats.err += 1;
      stats.lastError = error.message;
      console.error(`MQTT autostart failed: ${error.message}`);
    }
  }
});
