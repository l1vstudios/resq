#!/usr/bin/env python3
import argparse
import json
from datetime import datetime, timezone

import minimalmodbus
import serial


def build_device(port, slave, baudrate, timeout):
    dev = minimalmodbus.Instrument(port, slave)
    dev.serial.baudrate = baudrate
    dev.serial.bytesize = 8
    dev.serial.parity = serial.PARITY_NONE
    dev.serial.stopbits = 1
    dev.serial.timeout = timeout
    dev.mode = minimalmodbus.MODE_RTU
    dev.clear_buffers_before_each_transaction = True
    dev.close_port_after_each_call = False
    return dev


def main():
    parser = argparse.ArgumentParser(description="Scan EPEVER XTRA1206N via Modbus RTU.")
    parser.add_argument("--port", required=True)
    parser.add_argument("--slave", type=int, default=1)
    parser.add_argument("--baudrate", type=int, default=115200)
    parser.add_argument("--timeout", type=float, default=1.0)
    args = parser.parse_args()

    dev = build_device(args.port, args.slave, args.baudrate, args.timeout)

    def reg(addr):
        return dev.read_register(addr, 2, functioncode=4)

    def raw(addr):
        return dev.read_register(addr, 0, functioncode=4)

    def long32(addr):
        regs = dev.read_registers(addr, 2, functioncode=4)
        value = regs[0] | (regs[1] << 16)
        return value / 100.0

    def optional(reader):
        try:
            return reader()
        except Exception:
            return None

    payload = {
        "device": "EPEVER XTRA1206N",
        "protocol": "modbus_rtu",
        "port": args.port,
        "slave_address": args.slave,
        "scanned_at": datetime.now(timezone.utc).isoformat(),
        "values": {
            "pv_voltage": reg(0x3100),
            "pv_current": reg(0x3101),
            "pv_power": long32(0x3102),
            "charge_voltage": reg(0x3104),
            "charge_current": reg(0x3105),
            "charge_power": long32(0x3106),
            "battery_voltage": reg(0x3108),
            "load_voltage": reg(0x310C),
            "load_current": reg(0x310D),
            "load_power": long32(0x310E),
            "battery_soc": optional(lambda: raw(0x311A)),
            "battery_temperature": optional(lambda: reg(0x3110)),
            "controller_temperature": optional(lambda: reg(0x3111)),
        },
    }

    print(json.dumps(payload, separators=(",", ":")))


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:
        print(json.dumps({"ok": False, "message": str(exc)}))
        raise SystemExit(1)
