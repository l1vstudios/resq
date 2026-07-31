<?php

namespace App\Services\Mapping;

use RuntimeException;

final class MappingValidationException extends RuntimeException
{
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Mapping version is not valid.');
    }
}
