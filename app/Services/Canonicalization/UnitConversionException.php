<?php

namespace App\Services\Canonicalization;

use RuntimeException;

final class UnitConversionException extends RuntimeException
{
    public function __construct(public readonly string $reasonCode, string $message)
    {
        parent::__construct($message);
    }
}
