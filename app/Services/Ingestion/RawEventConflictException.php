<?php

namespace App\Services\Ingestion;

use App\Models\RawIngestionEvent;
use RuntimeException;

final class RawEventConflictException extends RuntimeException
{
    public function __construct(
        public readonly RawIngestionEvent $event,
        public readonly string $incomingPayloadHash,
    ) {
        parent::__construct('Logical event key already exists with different raw evidence.');
    }
}
