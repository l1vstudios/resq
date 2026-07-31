<?php

namespace App\Services\Canonicalization;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final class NamedPpcRegistry
{
    private const OUTPUTS = [
        'dew_point_spread' => 'dew_point_spread',
        'water_level_from_reference_distance' => 'water_level',
        'water_surface_elevation' => 'water_surface_elevation',
    ];

    public function approvedHandlers(): array
    {
        return array_keys(self::OUTPUTS);
    }

    public function shouldRun(string $handler, array $deviceParameterKeys): bool
    {
        $output = self::OUTPUTS[$handler] ?? throw new InvalidArgumentException('ppc_handler_not_approved');

        return ! in_array($output, $deviceParameterKeys, true);
    }

    /** @param array<string, string> $inputs */
    public function calculate(string $handler, array $inputs, array $deviceParameterKeys = []): ?string
    {
        if (! $this->shouldRun($handler, $deviceParameterKeys)) {
            return null;
        }

        return match ($handler) {
            'dew_point_spread' => $this->subtract($inputs, 'air_temperature', 'dew_point'),
            'water_level_from_reference_distance' => $this->subtract($inputs, 'reference_height', 'distance_to_water_surface'),
            'water_surface_elevation' => $this->subtract($inputs, 'reference_elevation', 'distance_to_water_surface'),
            default => throw new InvalidArgumentException('ppc_handler_not_approved'),
        };
    }

    private function subtract(array $inputs, string $left, string $right): string
    {
        if (! array_key_exists($left, $inputs) || ! array_key_exists($right, $inputs)) {
            throw new InvalidArgumentException('ppc_required_input_missing');
        }

        return BigDecimal::of($inputs[$left])->minus($inputs[$right])->__toString();
    }
}
