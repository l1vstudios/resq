<?php

namespace App\Services\Canonicalization;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Throwable;

final class DeterministicTransformer
{
    public const ENGINE_VERSION = 'canonical-php/2.2.0';

    private const MAX_NUMERIC_DIGITS = 80;

    private const MAX_EXPONENT_MAGNITUDE = 80;

    public function __construct(private readonly UnitConverter $unitConverter) {}

    public function transform(TransformationRequest $request): TransformationResult
    {
        $stages = [];
        $fingerprint = hash('sha256', json_encode($request->fingerprintPayload(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $stages[] = [
            'stage' => 'input_semantics',
            'status' => 'trusted',
            'classification' => $request->payloadSemantics,
            'evidence_sha256' => $request->evidenceHash ?? hash('sha256', $request->raw),
            'semantic_input_sha256' => hash('sha256', $request->raw),
        ];

        if (trim($request->canonicalParameterKey) === '') {
            $stages[] = ['stage' => 'mapping', 'status' => 'unmapped'];

            return $this->outcome($request, $fingerprint, $stages, 'unmapped', null, 'canonical_target_missing');
        }

        try {
            if ($request->payloadSemantics === 'pre_normalized' && trim($request->raw) === '') {
                $stages[] = ['stage' => 'extract', 'status' => 'missing'];

                return $this->outcome($request, $fingerprint, $stages, 'missing', null, 'source_value_absent');
            }

            $extracted = $this->extract($request);
            $stages[] = ['stage' => 'extract', 'status' => 'ok', 'hex' => bin2hex($extracted), 'bytes' => strlen($extracted)];

            $decoded = $this->decode($extracted, $request);
            $stages[] = ['stage' => 'decode', 'status' => 'ok', 'value' => $decoded];

            if ($this->isMissing($extracted, $decoded, $request->missingMarkers)) {
                $stages[] = ['stage' => 'missing_check', 'status' => 'missing'];

                return $this->outcome($request, $fingerprint, $stages, 'missing', null, 'matched_missing_marker');
            }
            $stages[] = ['stage' => 'missing_check', 'status' => 'present'];

            if ($request->targetDataType === 'text') {
                $stages[] = ['stage' => 'scale_offset', 'status' => 'bypassed', 'reason' => 'text_target'];
                $stages[] = ['stage' => 'unit_conversion', 'status' => 'bypassed', 'reason' => 'text_target'];
                $stages[] = ['stage' => 'round', 'status' => 'bypassed', 'reason' => 'text_target'];

                return $this->outcome($request, $fingerprint, $stages, 'value', $decoded, null);
            }

            $normalized = $request->payloadSemantics === 'pre_normalized'
                ? BigDecimal::of($decoded)->__toString()
                : BigDecimal::of($decoded)
                    ->multipliedBy($request->scale)
                    ->plus($request->offset)
                    ->__toString();
            if ($this->significantDigits($normalized) > 80) {
                return $this->outcome($request, $fingerprint, $stages, 'overflow', null, 'decimal_precision_limit');
            }
            $stages[] = $request->payloadSemantics === 'pre_normalized'
                ? ['stage' => 'scale_offset', 'status' => 'bypassed', 'reason' => 'pre_normalized_input', 'value' => $normalized]
                : ['stage' => 'scale_offset', 'status' => 'ok', 'scale' => $request->scale, 'offset' => $request->offset, 'value' => $normalized];

            if ($request->targetDataType === 'boolean') {
                $boolean = BigDecimal::of($normalized);
                if (! $boolean->isZero() && $boolean->compareTo(BigDecimal::one()) !== 0) {
                    return $this->outcome($request, $fingerprint, $stages, 'invalid', null, 'canonical_boolean_out_of_range');
                }
                $typedBoolean = $boolean->isZero() ? '0' : '1';
                $stages[] = ['stage' => 'unit_conversion', 'status' => 'bypassed', 'reason' => 'boolean_target'];
                $stages[] = ['stage' => 'round', 'status' => 'bypassed', 'reason' => 'boolean_target', 'value' => $typedBoolean];

                return $this->outcome($request, $fingerprint, $stages, 'value', $typedBoolean, null);
            }

            try {
                $converted = $this->unitConverter->convert($normalized, $request->sourceUnitCode, $request->targetUnitCode);
            } catch (UnitConversionException $exception) {
                $stages[] = ['stage' => 'unit_conversion', 'status' => 'failed', 'reason' => $exception->reasonCode];

                return $this->outcome($request, $fingerprint, $stages, 'conversion_failure', null, $exception->reasonCode);
            }
            $stages[] = ['stage' => 'unit_conversion', 'status' => 'ok', 'source_unit' => $request->sourceUnitCode, 'target_unit' => $request->targetUnitCode, 'value' => $converted];

            $rounded = BigDecimal::of($converted)
                ->toScale($request->outputPrecision, $this->roundingMode($request->roundingMode))
                ->__toString();
            $stages[] = ['stage' => 'round', 'status' => 'ok', 'precision' => $request->outputPrecision, 'mode' => $request->roundingMode, 'value' => $rounded];

            return $this->outcome($request, $fingerprint, $stages, 'value', $rounded, null);
        } catch (NonFiniteValue $exception) {
            $stages[] = ['stage' => 'decode', 'status' => 'failed', 'reason' => 'non_finite'];

            return $this->outcome($request, $fingerprint, $stages, 'non_finite', null, $exception->getMessage());
        } catch (OverflowValue $exception) {
            $stages[] = ['stage' => 'decode', 'status' => 'failed', 'reason' => 'overflow'];

            return $this->outcome($request, $fingerprint, $stages, 'overflow', null, $exception->getMessage());
        } catch (MathException $exception) {
            $stages[] = ['stage' => 'numeric', 'status' => 'failed', 'reason' => 'invalid_decimal'];

            return $this->outcome($request, $fingerprint, $stages, 'invalid', null, 'invalid_decimal');
        } catch (Throwable $exception) {
            $stages[] = ['stage' => 'transform', 'status' => 'failed', 'reason' => $exception->getMessage()];

            return $this->outcome($request, $fingerprint, $stages, 'invalid', null, $exception->getMessage());
        }
    }

    private function extract(TransformationRequest $request): string
    {
        $available = strlen($request->raw) - $request->byteOffset;
        $length = $request->length ?? $available;
        if ($available < 0 || $length < 1 || $available < $length) {
            throw new \InvalidArgumentException('extract_out_of_bounds');
        }

        return substr($request->raw, $request->byteOffset, $length);
    }

    private function decode(string $bytes, TransformationRequest $request): string
    {
        if ($request->inputMode === 'text') {
            $text = trim($bytes);
            if ($request->parser === 'text') {
                if ($text === '' || preg_match('//u', $text) !== 1) {
                    throw new \InvalidArgumentException('invalid_text');
                }

                return $text;
            }
            if (in_array(strtolower($text), ['nan', '+nan', '-nan', 'inf', '+inf', '-inf', 'infinity', '+infinity', '-infinity'], true)) {
                throw new NonFiniteValue('numeric_text_non_finite');
            }
            if ($request->parser === 'boolean') {
                return match (strtolower($text)) {
                    '1', 'true' => '1',
                    '0', 'false' => '0',
                    default => throw new \InvalidArgumentException('invalid_boolean'),
                };
            }
            if (! preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/D', $text)) {
                throw new \InvalidArgumentException('invalid_numeric_text');
            }
            $this->assertBoundedNumericText($text);

            return BigDecimal::of($text)->__toString();
        }

        $expected = match ($request->parser) {
            'boolean' => 1,
            'uint16', 'int16' => 2,
            'uint32', 'int32', 'float32' => 4,
            default => throw new \InvalidArgumentException('unsupported_parser'),
        };
        if (strlen($bytes) !== $expected) {
            throw new \InvalidArgumentException('parser_length_mismatch');
        }
        if ($expected === 1) {
            $value = ord($bytes);
            if ($value > 1) {
                throw new \InvalidArgumentException('invalid_boolean');
            }

            return (string) $value;
        }

        $ordered = $this->normalizeByteAndWordOrder($bytes, $request->byteOrder, $request->wordOrder);
        if ($request->parser === 'float32') {
            $value = unpack('Gvalue', $ordered)['value'];
            if (is_nan($value) || is_infinite($value)) {
                throw new NonFiniteValue('ieee754_non_finite');
            }

            return BigDecimal::of(sprintf('%.9g', $value))->__toString();
        }

        $unsigned = unpack($expected === 2 ? 'nvalue' : 'Nvalue', $ordered)['value'];
        if ($request->parser === 'int16' && $unsigned >= 0x8000) {
            $unsigned -= 0x10000;
        }
        if ($request->parser === 'int32' && $unsigned >= 0x80000000) {
            $unsigned -= 0x100000000;
        }

        return (string) $unsigned;
    }

    private function normalizeByteAndWordOrder(string $bytes, string $byteOrder, string $wordOrder): string
    {
        $words = str_split($bytes, 2);
        if ($byteOrder === 'little') {
            $words = array_map('strrev', $words);
        }
        if (count($words) === 2 && $wordOrder === 'low_high') {
            $words = array_reverse($words);
        }

        return implode('', $words);
    }

    private function isMissing(string $raw, string $decoded, array $markers): bool
    {
        foreach ($markers as $marker) {
            $marker = (string) $marker;
            if (str_starts_with($marker, 'hex:') && hash_equals(strtolower(substr($marker, 4)), strtolower(bin2hex($raw)))) {
                return true;
            }
            if (! str_starts_with($marker, 'hex:') && $decoded === $marker) {
                return true;
            }
        }

        return false;
    }

    private function roundingMode(string $mode): RoundingMode
    {
        return match ($mode) {
            'half_up' => RoundingMode::HalfUp,
            'half_even' => RoundingMode::HalfEven,
            'down' => RoundingMode::Down,
            'up' => RoundingMode::Up,
            default => throw new \InvalidArgumentException('unsupported_rounding_mode'),
        };
    }

    private function significantDigits(string $value): int
    {
        return strlen(preg_replace('/[^0-9]/', '', preg_replace('/^[+-]?0+/', '', $value)) ?: '0');
    }

    private function assertBoundedNumericText(string $value): void
    {
        preg_match(
            '/^[+-]?(?:(\d+)(?:\.(\d*))?|\.(\d+))(?:[eE]([+-]?\d+))?$/D',
            $value,
            $matches,
        );

        $mantissaDigits = ltrim(($matches[1] ?? '').($matches[2] ?? '').($matches[3] ?? ''), '0');
        if (strlen($mantissaDigits === '' ? '0' : $mantissaDigits) > self::MAX_NUMERIC_DIGITS) {
            throw new OverflowValue('numeric_text_too_wide');
        }

        $exponentText = $matches[4] ?? '0';
        $exponentDigits = ltrim(ltrim($exponentText, '+-'), '0');
        if (strlen($exponentDigits) > 2) {
            throw new OverflowValue('numeric_exponent_out_of_bounds');
        }

        $exponent = (int) $exponentText;
        if (abs($exponent) > self::MAX_EXPONENT_MAGNITUDE) {
            throw new OverflowValue('numeric_exponent_out_of_bounds');
        }
    }

    private function outcome(TransformationRequest $request, string $fingerprint, array $stages, string $status, ?string $value, ?string $reason): TransformationResult
    {
        $isValue = $status === 'value' && $value !== null;

        return new TransformationResult(
            status: $status,
            value: $value,
            dataType: $request->targetDataType,
            valueDecimal: $isValue && $request->targetDataType === 'decimal' ? $value : null,
            valueText: $isValue && $request->targetDataType === 'text' ? $value : null,
            valueBoolean: $isValue && $request->targetDataType === 'boolean' ? $value === '1' : null,
            unitCode: $value === null ? null : $request->targetUnitCode,
            reason: $reason,
            stages: $stages,
            origin: $request->origin,
            canonicalParameterKey: $request->canonicalParameterKey,
            mappingVersionIdentity: $request->mappingVersionIdentity,
            engineVersion: $request->engineVersion,
            runMode: $request->runMode,
            fingerprint: $fingerprint,
        );
    }
}

final class NonFiniteValue extends \RuntimeException {}
final class OverflowValue extends \RuntimeException {}
