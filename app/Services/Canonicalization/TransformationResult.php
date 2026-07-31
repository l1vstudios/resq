<?php

namespace App\Services\Canonicalization;

final class TransformationResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $value,
        public readonly string $dataType,
        public readonly ?string $valueDecimal,
        public readonly ?string $valueText,
        public readonly ?bool $valueBoolean,
        public readonly ?string $unitCode,
        public readonly ?string $reason,
        public readonly array $stages,
        public readonly string $origin,
        public readonly string $canonicalParameterKey,
        public readonly string $mappingVersionIdentity,
        public readonly string $engineVersion,
        public readonly string $runMode,
        public readonly string $fingerprint,
    ) {}

    public function isValue(): bool
    {
        return $this->status === 'value';
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
