<?php

namespace App\Services\Canonicalization;

use App\Models\CanonicalUnit;
use App\Models\CanonicalUnitConversion;
use Brick\Math\BigDecimal;

final class UnitConverter
{
    public function convert(string $value, ?string $sourceCode, ?string $targetCode): string
    {
        if ($sourceCode === null && $targetCode === null) {
            return BigDecimal::of($value)->__toString();
        }
        if ($sourceCode === null || $targetCode === null) {
            throw new UnitConversionException('unit_required', 'Source and target unit must both be defined.');
        }

        $source = CanonicalUnit::query()->where('code', $sourceCode)->where('is_active', true)->first();
        $target = CanonicalUnit::query()->where('code', $targetCode)->where('is_active', true)->first();
        if (! $source || ! $target) {
            throw new UnitConversionException('unknown_unit', 'Unit code is not registered or active.');
        }
        if ($sourceCode === $targetCode) {
            return BigDecimal::of($value)->__toString();
        }
        if ($source->dimension_key !== $target->dimension_key) {
            throw new UnitConversionException('incompatible_dimension', 'Source and target units have different dimensions.');
        }

        $conversion = CanonicalUnitConversion::query()
            ->where('source_unit_id', $source->id)
            ->where('target_unit_id', $target->id)
            ->where('is_approved', true)
            ->first();
        if (! $conversion) {
            throw new UnitConversionException('conversion_not_approved', 'No approved affine conversion exists for this unit pair.');
        }

        return BigDecimal::of($value)
            ->multipliedBy($conversion->multiplier)
            ->plus($conversion->offset)
            ->__toString();
    }
}
