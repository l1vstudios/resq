<?php

return [
    'ingestion' => [
        'max_payload_bytes' => (int) env('CANONICAL_MAX_RAW_PAYLOAD_BYTES', 1048576),
        'allowed_transports' => [
            'http',
            'mqtt',
            'modbus_tcp',
            'modbus_rtu',
            'rednode',
            'manual',
        ],
        'payload_classifications' => [
            'raw',
            'pre_normalized',
        ],
        'max_register_items' => 125,
        'max_heartbeat_readings' => 250,
        'rejection_status_codes' => [400, 413, 422],
        'rejection_reason_codes' => [
            'malformed_json',
            'payload_too_large',
            'validation_failed',
            'item_limit_exceeded',
            'heartbeat_limit_exceeded',
        ],
        'legacy_callback_tokens' => [
            'http_callback' => env('DEVICE_CALLBACK_TOKEN'),
            'rednode_callback' => env('REDNODE_CALLBACK_TOKEN'),
            'mqtt' => env('MQTT_CALLBACK_TOKEN'),
            'modbus_tcp' => env('MODBUS_CALLBACK_TOKEN'),
        ],
        'legacy_config_token' => env('REDNODE_CONFIG_TOKEN'),
        'legacy_logger_code' => env('DEVICE_CALLBACK_LOGGER_CODE')
            ?: env('REDNODE_LOGGER_CODE'),
    ],
    'ingress_rollout' => [
        'paths' => [
            'http_callback',
            'modbus_tcp',
            'mqtt',
            'rednode_callback',
            'rednode_heartbeat',
            'manual',
        ],
        'states' => [
            'expand',
            'shadow',
            'verified',
            'cutover',
            'rolled_back',
        ],
        'verification_suite_version' => 'ingress-convergence/1.0.0',
        'attestation_freshness_hours' => 24,
        'evidence_max_window_hours' => 168,
        'evidence_max_rows' => 1000,
        'reason_max_length' => 500,
    ],
];
