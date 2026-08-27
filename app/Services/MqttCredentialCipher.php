<?php

namespace App\Services;

use RuntimeException;

class MqttCredentialCipher
{
    public function encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return null;
        }

        $key = $this->key();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($ciphertext === false) {
            throw new RuntimeException('Credential MQTT gagal dienkripsi.');
        }

        return 'v1:'.base64_encode($iv).':'.base64_encode($tag).':'.base64_encode($ciphertext);
    }

    public function decrypt(?string $envelope): ?string
    {
        if (! $envelope) {
            return null;
        }

        $parts = explode(':', $envelope, 4);
        if (count($parts) !== 4 || $parts[0] !== 'v1') {
            throw new RuntimeException('Format credential MQTT tidak dikenali.');
        }

        $plaintext = openssl_decrypt(
            base64_decode($parts[3], true),
            'aes-256-gcm',
            $this->key(),
            OPENSSL_RAW_DATA,
            base64_decode($parts[1], true),
            base64_decode($parts[2], true)
        );

        if ($plaintext === false) {
            throw new RuntimeException('Credential MQTT gagal didekripsi. Periksa MQTT_CREDENTIAL_KEY.');
        }

        return $plaintext;
    }

    private function key(): string
    {
        $encoded = (string) config('services.mqtt.credential_key');
        $key = base64_decode($encoded, true);

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('MQTT_CREDENTIAL_KEY wajib berupa base64 dari 32 byte.');
        }

        return $key;
    }
}
