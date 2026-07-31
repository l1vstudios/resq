<?php

namespace App\Services\Ingestion;

use App\Services\Canonicalization\CanonicalProcessingResult;

final readonly class IngressResult
{
    public function __construct(
        public string $pathKey,
        public RawCaptureResult $capture,
        public ?CanonicalProcessingResult $canonical = null,
        public bool $projected = false,
        public ?string $projectedValue = null,
        public ?int $projectedCanonicalValueId = null,
        public ?string $processingFailure = null,
    ) {}
}
