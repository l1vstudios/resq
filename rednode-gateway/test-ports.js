require('dotenv').config({ quiet: true });

const http = require('http');
const https = require('https');
const ModbusRTU = require('modbus-serial');

const loggerCode = process.env.REDNODE_LOGGER_CODE || 'REDNODE-BLIIOT-01';
const configUrl = process.env.REDNODE_CONFIG_URL
  || `${String(process.env.APP_URL || 'http://127.0.0.1:8000').replace(/\/$/, '')}/api/rednode/config`;
const configToken = process.env.REDNODE_CONFIG_TOKEN || process.env.MODBUS_CALLBACK_TOKEN || process.env.MQTT_CALLBACK_TOKEN || '';
const jsonOutput = process.argv.includes('--json');

const ports = [
  { pins: 'PIN 1-2', mapping: 'Pin 1 = B, Pin 2 = A', port: '/dev/ttyAS4' },
  { pins: 'PIN 3-4', mapping: 'Pin 3 = B, Pin 4 = A', port: '/dev/ttyAS5' },
  { pins: 'PIN 5-6', mapping: 'Pin 5 = B, Pin 6 = A', port: '/dev/ttyAS2' },
  { pins: 'PIN 7-8', mapping: 'Pin 7 = B, Pin 8 = A', port: '/dev/ttyAS3' },
];

function numberEnv(name, fallback) {
  const value = Number(process.env[name]);
  return Number.isFinite(value) ? value : fallback;
}

function normalizeFunctionCode(value, fallback = 'FC03') {
  const normalized = String(value || fallback).toUpperCase().replace(/[^0-9]/g, '');
  return `FC${normalized.padStart(2, '0')}`;
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
          reject(new Error(`Invalid JSON from ${urlString}: ${text.slice(0, 200)}`));
          return;
        }

        if (response.statusCode < 200 || response.statusCode >= 300 || parsed.ok === false) {
          reject(new Error(parsed.message || `HTTP ${response.statusCode} from ${urlString}`));
          return;
        }

        resolve(parsed);
      });
    });

    request.on('timeout', () => {
      request.destroy(new Error(`HTTP timeout ${urlString}`));
    });
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

  return data;
}

async function readSensor(client, sensor) {
  const functionCode = normalizeFunctionCode(sensor.function_code);
  const address = Number(sensor.address || 0);
  const quantity = Math.max(Number(sensor.quantity || 1), String(sensor.data_type || '').includes('32') ? 2 : 1);

  client.setID(Number(sensor.slave_id || 1));

  if (functionCode === 'FC01') {
    return (await client.readCoils(address, quantity)).data;
  }
  if (functionCode === 'FC02') {
    return (await client.readDiscreteInputs(address, quantity)).data;
  }
  if (functionCode === 'FC04') {
    return (await client.readInputRegisters(address, quantity)).data;
  }

  return (await client.readHoldingRegisters(address, quantity)).data;
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

  const raw = Number(registers[0] || 0);
  return dataType.includes('int16') && raw > 0x7fff ? raw - 0x10000 : raw;
}

function valueText(registers, sensor) {
  const raw = valueFromRegisters(registers, sensor);
  const scale = Number(sensor.scale_factor ?? 1);
  const offset = Number(sensor.offset ?? 0);
  const value = (Number(raw) * (Number.isFinite(scale) ? scale : 1)) + (Number.isFinite(offset) ? offset : 0);
  const unit = String(sensor.unit || '').trim();
  const suffix = unit && unit !== '0' ? ` ${unit}` : '';

  return {
    raw,
    value,
    value_text: `${Number(value).toFixed(2)}${suffix}`,
  };
}

async function testPort(portConfig, config) {
  const serial = config.serial || {};
  const client = new ModbusRTU();
  const result = {
    ...portConfig,
    opened: false,
    tx_total: 0,
    rx_total: 0,
    sensors: [],
  };

  try {
    client.setTimeout(Number(serial.timeout_ms || process.env.REDNODE_TIMEOUT_MS || 1500));
    await client.connectRTUBuffered(portConfig.port, {
      baudRate: Number(serial.baud_rate || process.env.REDNODE_BAUD_RATE || 9600),
      dataBits: Number(serial.data_bits || process.env.REDNODE_DATA_BITS || 8),
      stopBits: Number(serial.stop_bits || process.env.REDNODE_STOP_BITS || 1),
      parity: serial.parity || process.env.REDNODE_PARITY || 'none',
    });
    result.opened = true;

    for (const sensor of config.sensors) {
      const sensorResult = {
        sensor_code: sensor.sensor_code,
        slave_id: Number(sensor.slave_id || 1),
        function_code: normalizeFunctionCode(sensor.function_code),
        address: Number(sensor.address || 0),
        tx: 0,
        rx: 0,
        registers: [],
        value: null,
        status: 'No Response',
        error: null,
      };

      try {
        sensorResult.tx += 1;
        result.tx_total += 1;
        const registers = await readSensor(client, sensor);
        const evaluated = valueText(registers, sensor);

        sensorResult.rx += 1;
        result.rx_total += 1;
        sensorResult.registers = registers;
        sensorResult.value = evaluated.value_text;
        sensorResult.raw = evaluated.raw;
        sensorResult.status = 'Connected';
      } catch (error) {
        sensorResult.error = error.message;
      }

      result.sensors.push(sensorResult);
    }
  } catch (error) {
    result.error = error.message;
    result.sensors = config.sensors.map((sensor) => ({
      sensor_code: sensor.sensor_code,
      slave_id: Number(sensor.slave_id || 1),
      function_code: normalizeFunctionCode(sensor.function_code),
      address: Number(sensor.address || 0),
      tx: 0,
      rx: 0,
      registers: [],
      value: null,
      status: 'Port Error',
      error: error.message,
    }));
  } finally {
    if (client.isOpen) {
      await new Promise((resolve) => client.close(resolve));
    }
  }

  return result;
}

async function main() {
  const config = await fetchConfig();

  if (!config.sensors.length) {
    throw new Error('Tidak ada sensor aktif untuk diuji.');
  }

  const results = [];
  for (const portConfig of ports) {
    results.push(await testPort(portConfig, config));
  }

  const payload = {
    ok: true,
    logger_code: loggerCode,
    generated_at: new Date().toISOString(),
    sensor_count: config.sensors.length,
    ports: results,
  };

  if (jsonOutput) {
    console.log(JSON.stringify(payload));
    return;
  }

  for (const port of results) {
    console.log(`\n${port.pins} ${port.port} ${port.mapping}`);
    console.log(`TX=${port.tx_total} RX=${port.rx_total}${port.error ? ` ERROR=${port.error}` : ''}`);
    for (const sensor of port.sensors) {
      console.log(`- ${sensor.sensor_code}: ${sensor.status} TX=${sensor.tx} RX=${sensor.rx} ${sensor.value || sensor.error || ''}`);
    }
  }
}

main().catch((error) => {
  const payload = {
    ok: false,
    message: error.message,
  };

  if (jsonOutput) {
    console.log(JSON.stringify(payload));
    process.exit(0);
  } else {
    console.error(error.message);
    process.exit(1);
  }
});
