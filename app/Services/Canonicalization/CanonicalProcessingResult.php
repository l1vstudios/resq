<?php

namespace App\Services\Canonicalization;

use App\Models\CanonicalProcessingRun;
use App\Models\CanonicalValue;
use Illuminate\Support\Collection;

final class CanonicalProcessingResult
{
    public function __construct(
        public readonly CanonicalProcessingRun $run,
        public readonly Collection $values,
        public readonly ?CanonicalValue $projectableValue,
        public readonly bool $reused,
        public readonly bool $mapped,
    ) {}
}
