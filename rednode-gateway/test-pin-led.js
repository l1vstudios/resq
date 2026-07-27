const { SerialPort } = require('serialport');
const readline = require('readline');

const TESTS = [
  {
    pins: 'PIN 1-2',
    mapping: 'Pin 1 = B, Pin 2 = A',
    port: '/dev/ttyAS4',
  },
  {
    pins: 'PIN 3-4',
    mapping: 'Pin 3 = B, Pin 4 = A',
    port: '/dev/ttyAS5',
  },
  {
    pins: 'PIN 5-6',
    mapping: 'Pin 5 = B, Pin 6 = A',
    port: '/dev/ttyAS2',
  },
  {
    pins: 'PIN 7-8',
    mapping: 'Pin 7 = B, Pin 8 = A',
    port: '/dev/ttyAS3',
  },
];

function parseArgs(argv) {
  const args = {
    auto: false,
    json: false,
    ports: [],
    startSlaveId: null,
    endSlaveId: null,
    baudRate: null,
    dataBits: null,
    parity: null,
    stopBits: null,
    functionCode: null,
    startRegister: null,
    quantity: null,
    responseTimeout: null,
    delayBetweenSlaves: null,
  };

  for (let index = 0; index < argv.length; index += 1) {
    const arg = argv[index];

    if (arg === '--auto' || arg === '--web') {
      args.auto = true;
      continue;
    }

    if (arg === '--json') {
      args.json = true;
      args.auto = true;
      continue;
    }

    if (!arg.startsWith('--')) {
      continue;
    }

    const normalizedArg = arg.replace(/^--/, '');
    const equalIndex = normalizedArg.indexOf('=');
    const key = equalIndex >= 0 ? normalizedArg.slice(0, equalIndex) : normalizedArg;
    let value = equalIndex >= 0 ? normalizedArg.slice(equalIndex + 1) : null;

    if (value === null && argv[index + 1] && !argv[index + 1].startsWith('--')) {
      value = argv[index + 1];
      index += 1;
    }

    if (key === 'start-slave') {
      args.startSlaveId = Number(value);
    }

    if (key === 'end-slave') {
      args.endSlaveId = Number(value);
    }

    if (key === 'port' && value) {
      args.ports.push(value);
    }

    if (key === 'ports' && value) {
      args.ports.push(...value.split(',').map((port) => port.trim()).filter(Boolean));
    }

    if (key === 'baud-rate') {
      args.baudRate = Number(value);
    }

    if (key === 'data-bits') {
      args.dataBits = Number(value);
    }

    if (key === 'parity') {
      args.parity = value;
    }

    if (key === 'stop-bits') {
      args.stopBits = Number(value);
    }

    if (key === 'function-code') {
      args.functionCode = Number(String(value || '').replace(/^FC/i, ''));
    }

    if (key === 'start-register') {
      args.startRegister = Number(value);
    }

    if (key === 'quantity') {
      args.quantity = Number(value);
    }

    if (key === 'response-timeout') {
      args.responseTimeout = Number(value);
    }

    if (key === 'delay-between-slaves') {
      args.delayBetweenSlaves = Number(value);
    }
  }

  return args;
}

const args = parseArgs(process.argv.slice(2));

function numberValue(value, fallback) {
  if (value === null || value === undefined || value === '') {
    return fallback;
  }

  return Number.isFinite(Number(value)) ? Number(value) : fallback;
}

function functionCodeValue(value, fallback) {
  if (value === null || value === undefined || value === '') {
    return fallback;
  }

  const normalized = String(value ?? '').toUpperCase().replace(/^FC/, '');
  const numeric = Number(normalized);

  return Number.isFinite(numeric) ? numeric : fallback;
}

const CONFIG = {
  baudRate: numberValue(args.baudRate, numberValue(process.env.REDNODE_BAUD_RATE, 9600)),
  dataBits: numberValue(args.dataBits, numberValue(process.env.REDNODE_DATA_BITS, 8)),
  parity: String(args.parity || process.env.REDNODE_PARITY || 'none').toLowerCase(),
  stopBits: numberValue(args.stopBits, numberValue(process.env.REDNODE_STOP_BITS, 1)),
  functionCode: functionCodeValue(args.functionCode, functionCodeValue(process.env.REDNODE_SCAN_FUNCTION_CODE, 3)),
  startRegister: numberValue(args.startRegister, numberValue(process.env.REDNODE_SCAN_START_REGISTER, 0)),
  quantity: numberValue(args.quantity, numberValue(process.env.REDNODE_SCAN_QUANTITY, 1)),
  responseTimeout: numberValue(args.responseTimeout, numberValue(process.env.REDNODE_SCAN_RESPONSE_TIMEOUT_MS, 300)),
  delayBetweenSlaves: numberValue(args.delayBetweenSlaves, numberValue(process.env.REDNODE_SCAN_DELAY_MS, 80)),
};

function validateConfig() {
  if (!Number.isInteger(CONFIG.baudRate) || CONFIG.baudRate < 300) {
    throw new Error('Baudrate tidak valid.');
  }

  if (![5, 6, 7, 8].includes(CONFIG.dataBits)) {
    throw new Error('Data bits tidak valid. Gunakan 5, 6, 7, atau 8.');
  }

  if (!['none', 'even', 'odd'].includes(CONFIG.parity)) {
    throw new Error('Parity tidak valid. Gunakan none, even, atau odd.');
  }

  if (![1, 2].includes(CONFIG.stopBits)) {
    throw new Error('Stop bits tidak valid. Gunakan 1 atau 2.');
  }

  if (![3, 4].includes(CONFIG.functionCode)) {
    throw new Error('Function code scan harus 3 atau 4.');
  }

  if (!Number.isInteger(CONFIG.startRegister) || CONFIG.startRegister < 0 || CONFIG.startRegister > 65535) {
    throw new Error('Register awal tidak valid.');
  }

  if (!Number.isInteger(CONFIG.quantity) || CONFIG.quantity < 1 || CONFIG.quantity > 125) {
    throw new Error('Quantity tidak valid. Gunakan 1 sampai 125.');
  }

  if (!Number.isInteger(CONFIG.responseTimeout) || CONFIG.responseTimeout < 50) {
    throw new Error('Timeout RX tidak valid.');
  }

  if (!Number.isInteger(CONFIG.delayBetweenSlaves) || CONFIG.delayBetweenSlaves < 0) {
    throw new Error('Delay slave tidak valid.');
  }
}

let rl = null;
const logs = [];

function log(message = '') {
  logs.push(String(message));

  if (!args.json) {
    console.log(message);
  }
}

function openReadline() {
  if (!rl) {
    rl = readline.createInterface({
      input: process.stdin,
      output: process.stdout,
    });
  }

  return rl;
}

function askQuestion(message) {
  return new Promise((resolve) => {
    openReadline().question(message, (answer) => {
      resolve(answer.trim());
    });
  });
}

function waitEnter(message) {
  if (args.auto) {
    return Promise.resolve();
  }

  return askQuestion(message);
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function formatHex(buffer) {
  if (!buffer || buffer.length === 0) {
    return '';
  }

  return buffer.toString('hex').match(/.{1,2}/g).join(' ');
}

function crc16(buffer) {
  let crc = 0xffff;

  for (const byte of buffer) {
    crc ^= byte;

    for (let index = 0; index < 8; index += 1) {
      crc = crc & 1 ? (crc >> 1) ^ 0xa001 : crc >> 1;
    }
  }

  return crc & 0xffff;
}

function isValidCrc(frame) {
  if (!frame || frame.length < 5) {
    return false;
  }

  const receivedCrc = frame[frame.length - 2] | (frame[frame.length - 1] << 8);
  const calculatedCrc = crc16(frame.subarray(0, frame.length - 2));

  return receivedCrc === calculatedCrc;
}

function createRequest(slaveId) {
  const frame = Buffer.alloc(8);

  frame[0] = slaveId;
  frame[1] = CONFIG.functionCode;
  frame.writeUInt16BE(CONFIG.startRegister, 2);
  frame.writeUInt16BE(CONFIG.quantity, 4);

  const crc = crc16(frame.subarray(0, 6));

  frame[6] = crc & 0xff;
  frame[7] = (crc >> 8) & 0xff;

  return frame;
}

function getExpectedFrameLength(buffer) {
  if (buffer.length < 2) {
    return null;
  }

  const functionCode = buffer[1];

  if (functionCode & 0x80) {
    return 5;
  }

  if (functionCode === 3 || functionCode === 4) {
    if (buffer.length < 3) {
      return null;
    }

    return 3 + buffer[2] + 2;
  }

  return null;
}

function parseResponse(frame) {
  const result = {
    slave_id: frame[0],
    function_code: frame[1],
    valid_crc: isValidCrc(frame),
    raw: formatHex(frame),
    exception: false,
    exception_code: null,
    registers: [],
  };

  if (result.function_code & 0x80) {
    result.exception = true;
    result.exception_code = frame[2];

    return result;
  }

  if (result.function_code === 3 || result.function_code === 4) {
    const byteCount = frame[2];

    for (let offset = 0; offset < byteCount; offset += 2) {
      const position = 3 + offset;

      if (position + 1 < frame.length - 2) {
        result.registers.push(frame.readUInt16BE(position));
      }
    }
  }

  return result;
}

function readSlaveResponse(serial, expectedSlaveId, timeoutMs) {
  return new Promise((resolve) => {
    let rxBuffer = Buffer.alloc(0);
    let finished = false;

    function finish(result) {
      if (finished) {
        return;
      }

      finished = true;
      clearTimeout(timeout);
      serial.removeListener('data', onData);
      resolve(result);
    }

    function onData(data) {
      rxBuffer = Buffer.concat([rxBuffer, data]);

      while (rxBuffer.length > 0 && rxBuffer[0] !== expectedSlaveId) {
        rxBuffer = rxBuffer.subarray(1);
      }

      const expectedLength = getExpectedFrameLength(rxBuffer);

      if (expectedLength !== null && rxBuffer.length >= expectedLength) {
        finish(rxBuffer.subarray(0, expectedLength));
      }
    }

    const timeout = setTimeout(() => finish(null), timeoutMs);
    serial.on('data', onData);
  });
}

function openSerialPort(path) {
  return new Promise((resolve, reject) => {
    const serial = new SerialPort({
      path,
      baudRate: CONFIG.baudRate,
      dataBits: CONFIG.dataBits,
      parity: CONFIG.parity,
      stopBits: CONFIG.stopBits,
      autoOpen: false,
    });

    serial.open((error) => {
      if (error) {
        reject(error);
        return;
      }

      resolve(serial);
    });
  });
}

function closeSerialPort(serial) {
  return new Promise((resolve) => {
    if (!serial || !serial.isOpen) {
      resolve();
      return;
    }

    serial.close(() => resolve());
  });
}

function flushSerialPort(serial) {
  return new Promise((resolve) => {
    serial.flush(() => resolve());
  });
}

function writeSerial(serial, frame) {
  return new Promise((resolve, reject) => {
    serial.write(frame, (error) => {
      if (error) {
        reject(error);
        return;
      }

      serial.drain((drainError) => {
        if (drainError) {
          reject(drainError);
          return;
        }

        resolve();
      });
    });
  });
}

function validSlaveRange(startSlaveId, endSlaveId) {
  return Number.isInteger(startSlaveId)
    && Number.isInteger(endSlaveId)
    && startSlaveId >= 1
    && startSlaveId <= 247
    && endSlaveId >= 1
    && endSlaveId <= 247
    && startSlaveId <= endSlaveId;
}

async function inputSlaveRange() {
  if (args.auto) {
    const startSlaveId = args.startSlaveId ?? Number(process.env.REDNODE_SCAN_START_SLAVE || 1);
    const endSlaveId = args.endSlaveId ?? Number(process.env.REDNODE_SCAN_END_SLAVE || startSlaveId);

    if (!validSlaveRange(startSlaveId, endSlaveId)) {
      throw new Error('Range Slave ID tidak valid. Gunakan angka 1 sampai 247.');
    }

    return { startSlaveId, endSlaveId };
  }

  while (true) {
    log('\n========================================');
    log('INPUT RANGE SLAVE ID');
    log('========================================');

    const startSlaveId = Number(await askQuestion('Masukkan Slave ID awal (1-247): '));
    const endSlaveId = Number(await askQuestion('Masukkan Slave ID akhir (1-247): '));

    if (validSlaveRange(startSlaveId, endSlaveId)) {
      return { startSlaveId, endSlaveId };
    }

    log('\nRange Slave ID tidak valid.');
    log('Slave ID harus berupa angka 1 sampai 247.');
    log('Slave ID awal tidak boleh lebih besar dari Slave ID akhir.');
  }
}

async function scanSlave(serial, slaveId) {
  const request = createRequest(slaveId);

  await flushSerialPort(serial);
  log(`TX Slave ${slaveId}: ${formatHex(request)}`);

  const responsePromise = readSlaveResponse(serial, slaveId, CONFIG.responseTimeout);
  await writeSerial(serial, request);
  const responseFrame = await responsePromise;

  if (!responseFrame) {
    log(`RX Slave ${slaveId}: tidak ada respons`);
    return {
      slave_id: slaveId,
      tx: formatHex(request),
      rx: null,
      registers: [],
      status: 'no-response',
    };
  }

  const response = parseResponse(responseFrame);
  log(`RX Slave ${response.slave_id}: ${response.raw}`);

  if (!response.valid_crc) {
    log('  Status: CRC tidak valid');

    return {
      ...response,
      tx: formatHex(request),
      rx: response.raw,
      status: 'crc-invalid',
    };
  }

  if (response.exception) {
    log(`  Status: Modbus exception ${response.exception_code}`);

    return {
      ...response,
      tx: formatHex(request),
      rx: response.raw,
      status: 'exception',
    };
  }

  log('  Status: respons valid');
  response.registers.forEach((value, index) => {
    const registerAddress = CONFIG.startRegister + index;
    log(`  Register ${registerAddress}: ${value} (0x${value.toString(16).padStart(4, '0')})`);
  });

  return {
    ...response,
    tx: formatHex(request),
    rx: response.raw,
    status: 'valid',
  };
}

async function testPort(test, slaveRange) {
  log('\n========================================');
  log(`SCAN ${test.pins}`);
  log(test.mapping);
  log(`Device Linux: ${test.port}`);
  log(`Slave ID: ${slaveRange.startSlaveId}-${slaveRange.endSlaveId}`);
  log(`Function: ${CONFIG.functionCode}`);
  log(`Register awal: ${CONFIG.startRegister}`);
  log(`Quantity: ${CONFIG.quantity}`);
  log('========================================\n');

  let serial;
  const result = {
    ...test,
    ok: true,
    error: null,
    tx_total: 0,
    rx_total: 0,
    slaves: [],
  };

  try {
    serial = await openSerialPort(test.port);
    log(`${test.port} berhasil dibuka.`);
    log('Mulai scan...\n');

    for (let slaveId = slaveRange.startSlaveId; slaveId <= slaveRange.endSlaveId; slaveId += 1) {
      try {
        const response = await scanSlave(serial, slaveId);
        result.tx_total += 1;

        if (response.status !== 'no-response') {
          result.rx_total += 1;
          result.slaves.push(response);
        }
      } catch (error) {
        log(`ERROR Slave ${slaveId}: ${error.message}`);
        result.slaves.push({
          slave_id: slaveId,
          registers: [],
          status: 'error',
          error: error.message,
        });
      }

      await sleep(CONFIG.delayBetweenSlaves);
    }

    log('\n========================================');
    log(`HASIL SCAN ${test.pins}`);
    log('========================================');

    if (result.slaves.length === 0) {
      log('Tidak ada slave yang memberikan respons.');
    } else {
      log(`Jumlah slave merespons: ${result.slaves.length}`);

      result.slaves.forEach((slave) => {
        log(`\nSlave ID: ${slave.slave_id}`);
        log(`Status: ${slave.status}`);
        log(`RX: ${slave.raw || '-'}`);

        if (slave.registers.length > 0) {
          slave.registers.forEach((value, index) => {
            log(`Register ${CONFIG.startRegister + index}: ${value}`);
          });
        }

        if (slave.exception) {
          log(`Exception code: ${slave.exception_code}`);
        }
      });
    }
  } catch (error) {
    result.ok = false;
    result.error = error.message;
    log(`Gagal membuka ${test.port}: ${error.message}`);
  } finally {
    await closeSerialPort(serial);
  }

  return result;
}

function selectedTests() {
  if (!args.ports.length) {
    return TESTS;
  }

  const selected = new Set(args.ports);

  return TESTS.filter((test) => selected.has(test.port));
}

async function main() {
  validateConfig();

  log('========================================');
  log('SCAN SLAVE MODBUS BL118');
  log('========================================');
  log(args.auto
    ? 'Mode web aktif: input diambil dari parameter command.'
    : 'Mode terminal aktif: isi range slave lewat prompt.');
  log('Kalau port serial sedang dipakai gateway, hentikan gateway sementara sebelum scan.');
  log(`Konfigurasi serial: ${CONFIG.baudRate} baud, ${CONFIG.dataBits}${CONFIG.parity[0].toUpperCase()}${CONFIG.stopBits}`);

  const slaveRange = await inputSlaveRange();
  const tests = selectedTests();

  if (tests.length === 0) {
    throw new Error('Tidak ada port yang cocok untuk discan.');
  }

  log('\nRange scan yang dipilih:');
  log(`Slave ${slaveRange.startSlaveId} sampai ${slaveRange.endSlaveId}`);

  const allResults = [];

  for (const test of tests) {
    await waitEnter(`\nTekan ENTER untuk scan ${test.pins} (${test.port})...`);
    allResults.push(await testPort(test, slaveRange));
    await sleep(500);
  }

  log('\n\n========================================');
  log('RINGKASAN SEMUA PORT');
  log('========================================');

  allResults.forEach((result) => {
    log(`\n${result.pins} -> ${result.port}`);

    if (!result.slaves.length) {
      log(result.error ? `  Error: ${result.error}` : '  Tidak ada respons.');
      return;
    }

    result.slaves.forEach((slave) => {
      const values = slave.registers.length > 0 ? slave.registers.join(', ') : '-';
      log(`  Slave ${slave.slave_id} | Status ${slave.status} | Nilai ${values}`);
    });
  });

  log('\nScan selesai.');

  return {
    ok: true,
    message: 'Scan selesai.',
    config: CONFIG,
    slave_range: slaveRange,
    ports: allResults,
    logs,
  };
}

main()
  .then((result) => {
    if (args.json) {
      console.log(JSON.stringify(result));
    }
  })
  .catch((error) => {
    const result = {
      ok: false,
      message: error.message,
      config: CONFIG,
      ports: [],
      logs,
    };

    if (args.json) {
      console.log(JSON.stringify(result));
    } else {
      console.error(`Fatal error: ${error.message}`);
    }

    process.exit(1);
  })
  .finally(() => {
    if (rl) {
      rl.close();
    }
  });
