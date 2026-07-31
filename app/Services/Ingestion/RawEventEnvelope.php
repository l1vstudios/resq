<?php

namespace App\Services\Ingestion;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class RawEventEnvelope
{
    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public string $logicalEventKey,
        public string $transport,
        public string $payloadClassification,
        public string $exactPayload,
        public array $sourceSnapshot,
        public ?int $projectId = null,
        public ?int $monitoringStationId = null,
        public ?int $dataLoggerId = null,
        public ?int $sensorId = null,
        public ?string $contentType = null,
        public ?string $contentEncoding = null,
        public ?array $inspectionPayload = null,
        public ?CarbonImmutable $observedAt = null,
        public string $observedAtProvenance = 'device',
        public bool $observedAtFallback = false,
        public string $envelopeVersion = '1',
        public string $receiptStatus = 'accepted',
        public ?string $failureReason = null,
        public array $items = [],
    ) {
        $this->validate();
    }

    public function payloadHash(): string
    {
        return hash('sha256', $this->exactPayload);
    }

    public function payloadSize(): int
    {
        return strlen($this->exactPayload);
    }

    private function validate(): void
    {
        if ($this->sourceType === '' || $this->sourceId < 1) {
            throw new InvalidArgumentException('A server-resolved source identity is required.');
        }

        if ($this->logicalEventKey === '' || strlen($this->logicalEventKey) > 191) {
            throw new InvalidArgumentException('A non-empty logical event key of at most 191 bytes is required.');
        }

        $allowedTransports = config('canonical.ingestion.allowed_transports', []);
        if (! in_array($this->transport, $allowedTransports, true)) {
            throw new InvalidArgumentException('Unsupported ingestion transport.');
        }

        $allowedClassifications = config('canonical.ingestion.payload_classifications', []);
        if (! in_array($this->payloadClassification, $allowedClassifications, true)) {
            throw new InvalidArgumentException('Unsupported payload classification.');
        }

        $maximumBytes = (int) config('canonical.ingestion.max_payload_bytes', 1048576);
        if ($this->payloadSize() > $maximumBytes) {
            throw new InvalidArgumentException('Raw payload exceeds the configured size limit.');
        }

        if ($this->observedAtFallback && $this->observedAtProvenance === 'device') {
            throw new InvalidArgumentException('Fallback observation time must declare non-device provenance.');
        }
    }
}
