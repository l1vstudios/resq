<?php

namespace App\Services\Canonicalization;

use InvalidArgumentException;

final class TransformationRequest
{
    public function __construct(
        public readonly string $raw,
        public readonly string $inputMode,
        public readonly string $parser,
        public readonly int $byteOffset,
        public readonly ?int $length,
        public readonly string $byteOrder,
        public readonly string $wordOrder,
        public readonly string $scale,
        public readonly string $offset,
        public readonly ?string $sourceUnitCode,
        public readonly ?string $targetUnitCode,
        public readonly array $missingMarkers,
        public readonly string $canonicalParameterKey,
        public readonly int $outputPrecision,
        public readonly string $roundingMode,
        public readonly string $origin,
        public readonly string $mappingVersionIdentity,
        public readonly string $runMode,
        public readonly string $targetDataType = 'decimal',
        public readonly string $payloadSemantics = 'raw',
        public readonly ?string $evidenceHash = null,
        public readonly string $engineVersion = DeterministicTransformer::ENGINE_VERSION,
    ) {
        if (! in_array($inputMode, ['binary', 'text'], true)) {
            throw new InvalidArgumentException('inputMode must be binary or text.');
        }
        if (! in_array($byteOrder, ['big', 'little'], true)) {
            throw new InvalidArgumentException('byteOrder must be big or little.');
        }
        if (! in_array($wordOrder, ['high_low', 'low_high'], true)) {
            throw new InvalidArgumentException('wordOrder must be high_low or low_high.');
        }
        if (! in_array($origin, ['RDM', 'RDP', 'PPC'], true)) {
            throw new InvalidArgumentException('origin must be RDM, RDP, or PPC.');
        }
        if (! in_array($runMode, ['preview', 'live', 'replay'], true)) {
            throw new InvalidArgumentException('runMode must be preview, live, or replay.');
        }
        if (! in_array($payloadSemantics, ['raw', 'pre_normalized'], true)) {
            throw new InvalidArgumentException('payloadSemantics must be raw or pre_normalized.');
        }
        if (! in_array($targetDataType, ['decimal', 'text', 'boolean'], true)) {
            throw new InvalidArgumentException('targetDataType must be decimal, text, or boolean.');
        }
        if ($evidenceHash !== null && ! preg_match('/^[a-f0-9]{64}$/D', $evidenceHash)) {
            throw new InvalidArgumentException('evidenceHash must be a lowercase SHA-256 digest.');
        }
        if ($byteOffset < 0 || $outputPrecision < 0 || $outputPrecision > 18) {
            throw new InvalidArgumentException('Invalid byte offset or output precision.');
        }
    }

    public function fingerprintPayload(): array
    {
        return [
            'raw_sha256' => hash('sha256', $this->raw),
            'evidence_sha256' => $this->evidenceHash ?? hash('sha256', $this->raw),
            'payload_semantics' => $this->payloadSemantics,
            'input_mode' => $this->inputMode,
            'parser' => $this->parser,
            'byte_offset' => $this->byteOffset,
            'length' => $this->length,
            'byte_order' => $this->byteOrder,
            'word_order' => $this->wordOrder,
            'scale' => $this->scale,
            'offset' => $this->offset,
            'source_unit' => $this->sourceUnitCode,
            'target_unit' => $this->targetUnitCode,
            'missing_markers' => array_values($this->missingMarkers),
            'canonical_parameter' => $this->canonicalParameterKey,
            'target_data_type' => $this->targetDataType,
            'precision' => $this->outputPrecision,
            'rounding_mode' => $this->roundingMode,
            'origin' => $this->origin,
            'mapping_version' => $this->mappingVersionIdentity,
            'run_mode' => $this->runMode,
            'engine_version' => $this->engineVersion,
        ];
    }
}
