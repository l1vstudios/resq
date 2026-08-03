#!/usr/bin/env node
/**
 * Modbus Register Scanner
 * Scan a range of Modbus registers to find which ones contain changing values.
 *
 * Usage:
 *   node scan-registers.js [options]
 *
 * Options:
 *   --port=/dev/ttyAS2     Serial port (default: /dev/ttyAS2)
 *   --baud=9600            Baud rate (default: 9600)
 *   --slave=1              Slave ID (default: 1)
 *   --start=0              Start register address (default: 0)
 *   --count=20             Number of registers to read (default: 20)
 *   --fc=3                 Function code: 3=holding, 4=input (default: 3)
 *   --interval=2000        Interval between scans in ms (default: 2000)
 *   --scans=5              Number of scans to perform (default: 5)
 */

const ModbusRTU = require('modbus-serial');

const args = process.argv.slice(2).reduce((acc, arg) => {
  const [key, value] = arg.replace(/^--/, '').split('=');
  acc[key] = value;
  return acc;
}, {});

const port = args.port || '/dev/ttyAS2';
const baudRate = Number(args.baud || 9600);
const slaveId = Number(args.slave || 1);
const startAddress = Number(args.start || 0);
const registerCount = Number(args.count || 20);
const functionCode = Number(args.fc || 3);
const scanInterval = Number(args.interval || 2000);
const totalScans = Number(args.scans || 5);

const modbus = new ModbusRTU();
const history = [];

async function readRegisters() {
  modbus.setID(slaveId);

  if (functionCode === 4) {
    return (await modbus.readInputRegisters(startAddress, registerCount)).data;
  }
  return (await modbus.readHoldingRegisters(startAddress, registerCount)).data;
}

function printHeader() {
  console.log(`\n=== Modbus Register Scanner ===`);
  console.log(`Port: ${port} @ ${baudRate} baud`);
  console.log(`Slave ID: ${slaveId}`);
  console.log(`Function Code: FC0${functionCode} (${functionCode === 3 ? 'Holding Registers' : 'Input Registers'})`);
  console.log(`Address Range: ${startAddress} - ${startAddress + registerCount - 1}`);
  console.log(`Scanning ${totalScans} times with ${scanInterval}ms interval...\n`);
}

function printResults() {
  console.log('\n=== SCAN RESULTS ===\n');

  const columns = history.length;
  const header = ['Addr', ...history.map((_, i) => `Scan ${i + 1}`), 'Changed?'];
  console.log(header.join('\t'));
  console.log('-'.repeat(header.join('\t').length + 20));

  for (let i = 0; i < registerCount; i++) {
    const addr = startAddress + i;
    const values = history.map(scan => scan[i]);
    const uniqueValues = new Set(values);
    const changed = uniqueValues.size > 1;

    const row = [
      addr.toString().padStart(4, ' '),
      ...values.map(v => v.toString().padStart(6, ' ')),
      changed ? '>>> YES <<<' : 'no'
    ];

    if (changed) {
      console.log('\x1b[32m%s\x1b[0m', row.join('\t')); // Green for changed
    } else {
      console.log(row.join('\t'));
    }
  }

  console.log('\n=== SUMMARY ===');
  const changingAddresses = [];
  for (let i = 0; i < registerCount; i++) {
    const values = history.map(scan => scan[i]);
    if (new Set(values).size > 1) {
      changingAddresses.push(startAddress + i);
    }
  }

  if (changingAddresses.length > 0) {
    console.log(`Registers with changing values: ${changingAddresses.join(', ')}`);
    console.log('\nThese are likely the measurement registers. Update your sensor config to use one of these addresses.');
  } else {
    console.log('No registers changed during the scan period.');
    console.log('\nPossible reasons:');
    console.log('  1. The sensor value is genuinely stable (unlikely for humidity/temperature)');
    console.log('  2. Wrong address range - try scanning higher addresses (--start=100 --count=50)');
    console.log('  3. Wrong function code - try FC04 instead of FC03 (--fc=4)');
    console.log('  4. Wrong slave ID - check sensor documentation');
    console.log('  5. Sensor not properly connected or powered');
  }
}

async function main() {
  printHeader();

  try {
    await modbus.connectRTUBuffered(port, { baudRate, dataBits: 8, stopBits: 1, parity: 'none' });
    modbus.setTimeout(1500);
    console.log(`Connected to ${port}\n`);

    for (let scan = 0; scan < totalScans; scan++) {
      try {
        const registers = await readRegisters();
        history.push(registers);
        console.log(`Scan ${scan + 1}/${totalScans}: [${registers.slice(0, 10).join(', ')}${registers.length > 10 ? '...' : ''}]`);

        if (scan < totalScans - 1) {
          await new Promise(resolve => setTimeout(resolve, scanInterval));
        }
      } catch (error) {
        console.error(`Scan ${scan + 1} error: ${error.message}`);
        history.push(new Array(registerCount).fill(null));
      }
    }

    printResults();

  } catch (error) {
    console.error(`Connection error: ${error.message}`);
  } finally {
    if (modbus.isOpen) {
      await new Promise(resolve => modbus.close(resolve));
    }
  }
}

main().catch(console.error);
