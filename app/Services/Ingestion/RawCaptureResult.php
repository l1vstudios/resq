<?php

namespace App\Services\Ingestion;

use App\Models\RawIngestionEvent;

final readonly class RawCaptureResult
{
    public function __construct(
        public RawIngestionEvent $event,
        public bool $idempotent,
    ) {
    }
}
