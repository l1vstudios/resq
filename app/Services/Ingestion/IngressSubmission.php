<?php

namespace App\Services\Ingestion;

use App\Models\Sensor;
use InvalidArgumentException;

final readonly class IngressSubmission
{
    public function __construct(
        public string $pathKey,
        public RawEventEnvelope $envelope,
        public ?Sensor $sensor = null,
        public ?array $compatibilityCandidate = null,
    ) {
        if ($this->pathKey === '' || strlen($this->pathKey) > 64) {
            throw new InvalidArgumentException('A trusted ingress path key of at most 64 bytes is required.');
        }

        if ($this->sensor && $this->envelope->sensorId !== $this->sensor->id) {
            throw new InvalidArgumentException('Ingress sensor identity must match the trusted raw envelope.');
        }

        if ($this->compatibilityCandidate !== null && ! $this->sensor) {
            throw new InvalidArgumentException('A compatibility candidate requires a trusted sensor identity.');
        }
    }
}
