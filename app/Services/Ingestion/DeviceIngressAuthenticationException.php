<?php

namespace App\Services\Ingestion;

use RuntimeException;

final class DeviceIngressAuthenticationException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 403)
    {
        parent::__construct($message);
    }
}
