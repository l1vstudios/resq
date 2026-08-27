const assert = require('node:assert/strict');
const crypto = require('node:crypto');
const test = require('node:test');
const { dataGet, decryptCredential } = require('./mqtt-runtime');

test('dataGet resolves dotted MQTT payload paths', () => {
  assert.equal(dataGet({ data: { temperature: 28.5 } }, 'data.temperature'), 28.5);
  assert.equal(dataGet({ sensor_code: 'SNS-01' }, 'sensor_code'), 'SNS-01');
  assert.equal(dataGet({}, 'missing.value'), undefined);
});

test('decryptCredential reads the PHP-compatible AES-256-GCM envelope', () => {
  const key = Buffer.alloc(32, 'k');
  const iv = Buffer.alloc(12, 'i');
  const cipher = crypto.createCipheriv('aes-256-gcm', key, iv);
  const ciphertext = Buffer.concat([cipher.update('broker-secret', 'utf8'), cipher.final()]);
  const envelope = `v1:${iv.toString('base64')}:${cipher.getAuthTag().toString('base64')}:${ciphertext.toString('base64')}`;
  process.env.MQTT_CREDENTIAL_KEY = key.toString('base64');

  assert.equal(decryptCredential(envelope), 'broker-secret');
});
