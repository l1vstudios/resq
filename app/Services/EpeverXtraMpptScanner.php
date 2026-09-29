<?php

namespace App\Services;

use RuntimeException;

class EpeverXtraMpptScanner
{
    public function scan(array $config): array
    {
        $port = trim((string) ($config['serial_port'] ?? ''));
        if ($port === '') {
            throw new RuntimeException('Serial port belum diisi.');
        }

        if (! is_readable($port) || ! is_writable($port)) {
            throw new RuntimeException("Serial port {$port} tidak bisa dibaca/ditulis oleh PHP.");
        }

        $slave = (int) ($config['slave_address'] ?? 1);
        if ($slave < 1 || $slave > 247) {
            throw new RuntimeException('Slave address Modbus harus 1 sampai 247.');
        }

        $timeoutMs = max(300, min(10000, (int) ($config['timeout_ms'] ?? 1000)));
        $this->configureSerialPort($port, [
            'baud_rate' => (int) ($config['baud_rate'] ?? 115200),
            'data_bits' => (int) ($config['data_bits'] ?? 8),
            'parity' => strtolower((string) ($config['parity'] ?? 'none')),
            'stop_bits' => (int) ($config['stop_bits'] ?? 1),
            'timeout_ms' => $timeoutMs,
        ]);

        $handle = @fopen($port, 'r+b');
        if (! is_resource($handle)) {
            throw new RuntimeException("Gagal membuka serial port {$port}.");
        }

        stream_set_blocking($handle, true);
        stream_set_timeout($handle, intdiv($timeoutMs, 1000), ($timeoutMs % 1000) * 1000);

        try {
            $values = [
                'pv_voltage' => $this->readScaledRegister($handle, $slave, 0x3100),
                'pv_current' => $this->readScaledRegister($handle, $slave, 0x3101),
                'pv_power' => $this->readLong32($handle, $slave, 0x3102),
                'charge_voltage' => $this->readScaledRegister($handle, $slave, 0x3104),
                'charge_current' => $this->readScaledRegister($handle, $slave, 0x3105),
                'charge_power' => $this->readLong32($handle, $slave, 0x3106),
                'battery_voltage' => $this->readScaledRegister($handle, $slave, 0x3108),
                'load_voltage' => $this->readScaledRegister($handle, $slave, 0x310C),
                'load_current' => $this->readScaledRegister($handle, $slave, 0x310D),
                'load_power' => $this->readLong32($handle, $slave, 0x310E),
                'battery_soc' => $this->readOptional(fn () => $this->readRawRegister($handle, $slave, 0x311A)),
                'battery_temperature' => $this->readOptional(fn () => $this->readScaledRegister($handle, $slave, 0x3110)),
                'controller_temperature' => $this->readOptional(fn () => $this->readScaledRegister($handle, $slave, 0x3111)),
            ];
        } finally {
            fclose($handle);
        }

        return [
            'device' => 'EPEVER XTRA1206N',
            'protocol' => 'modbus_rtu',
            'port' => $port,
            'slave_address' => $slave,
            'scanned_at' => now()->toISOString(),
            'values' => $values,
        ];
    }

    public function availablePorts(): array
    {
        $patterns = PHP_OS_FAMILY === 'Darwin'
            ? ['/dev/cu.usbserial*', '/dev/cu.SLAB_USBtoUART*', '/dev/cu.wchusbserial*', '/dev/cu.usbmodem*']
            : ['/dev/ttyUSB*', '/dev/ttyACM*', '/dev/serial/by-id/*'];

        return collect($patterns)
            ->flatMap(fn (string $pattern) => glob($pattern) ?: [])
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function readScaledRegister($handle, int $slave, int $address): float
    {
        return $this->readRawRegister($handle, $slave, $address) / 100.0;
    }

    private function readRawRegister($handle, int $slave, int $address): int
    {
        $registers = $this->readRegisters($handle, $slave, $address, 1);

        return $registers[0];
    }

    private function readLong32($handle, int $slave, int $address): float
    {
        $registers = $this->readRegisters($handle, $slave, $address, 2);
        $value = $registers[0] | ($registers[1] << 16);

        return $value / 100.0;
    }

    private function readRegisters($handle, int $slave, int $address, int $quantity): array
    {
        $request = pack('CCnn', $slave, 4, $address, $quantity);
        $request .= $this->crcBytes($request);

        if (@fwrite($handle, $request) === false) {
            throw new RuntimeException('Gagal menulis request Modbus ke serial port.');
        }

        fflush($handle);

        $expectedLength = 5 + ($quantity * 2);
        $response = $this->readBytes($handle, $expectedLength);

        if (strlen($response) < $expectedLength) {
            throw new RuntimeException('Respons Modbus timeout atau tidak lengkap.');
        }

        $payload = substr($response, 0, -2);
        if ($this->crcBytes($payload) !== substr($response, -2)) {
            throw new RuntimeException('CRC respons Modbus tidak valid.');
        }

        $header = unpack('Cslave/Cfunction/Cbytes', substr($payload, 0, 3));
        if ((int) $header['slave'] !== $slave) {
            throw new RuntimeException('Respons Modbus berasal dari slave address berbeda.');
        }

        if (((int) $header['function'] & 0x80) !== 0) {
            $code = ord(substr($payload, 2, 1));
            throw new RuntimeException("Device mengembalikan Modbus exception {$code}.");
        }

        if ((int) $header['function'] !== 4 || (int) $header['bytes'] !== $quantity * 2) {
            throw new RuntimeException('Format respons Modbus tidak sesuai.');
        }

        $registers = [];
        $data = substr($payload, 3);
        for ($i = 0; $i < $quantity; $i++) {
            $registers[] = unpack('n', substr($data, $i * 2, 2))[1];
        }

        return $registers;
    }

    private function readBytes($handle, int $length): string
    {
        $buffer = '';
        while (strlen($buffer) < $length && ! feof($handle)) {
            $chunk = fread($handle, $length - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($handle);
                if (! empty($meta['timed_out'])) {
                    break;
                }
                usleep(20000);
                continue;
            }
            $buffer .= $chunk;
        }

        return $buffer;
    }

    private function readOptional(callable $reader): mixed
    {
        try {
            return $reader();
        } catch (\Throwable) {
            return null;
        }
    }

    private function crcBytes(string $binary): string
    {
        $crc = 0xFFFF;
        $length = strlen($binary);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= ord($binary[$i]);
            for ($bit = 0; $bit < 8; $bit++) {
                if (($crc & 0x0001) !== 0) {
                    $crc = ($crc >> 1) ^ 0xA001;
                } else {
                    $crc >>= 1;
                }
            }
        }

        return pack('v', $crc);
    }

    private function configureSerialPort(string $port, array $config): void
    {
        $baud = (int) $config['baud_rate'];
        $dataBits = (int) $config['data_bits'];
        $stopBits = (int) $config['stop_bits'];
        $parity = strtolower((string) $config['parity']);
        $timeoutTenths = max(1, (int) ceil(((int) $config['timeout_ms']) / 100));

        $command = PHP_OS_FAMILY === 'Darwin'
            ? ['stty', '-f', $port]
            : ['stty', '-F', $port];

        $parityEnabled = in_array($parity, ['even', 'odd'], true);

        $args = [
            (string) $baud,
            'cs'.$dataBits,
            $stopBits === 2 ? 'cstopb' : '-cstopb',
            $parityEnabled ? 'parenb' : '-parenb',
            $parity === 'odd' ? 'parodd' : '-parodd',
            'raw',
            '-echo',
            'min',
            '0',
            'time',
            (string) $timeoutTenths,
        ];

        $fullCommand = implode(' ', array_map('escapeshellarg', [...$command, ...$args])).' 2>&1';
        exec($fullCommand, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException('Gagal konfigurasi serial port: '.implode("\n", $output));
        }
    }
}
