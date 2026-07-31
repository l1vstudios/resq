<?php

namespace App\Services\Mapping;

use App\Models\CanonicalUnitConversion;
use App\Models\MappingProfileVersion;
use Brick\Math\BigDecimal;
use Throwable;

final class MappingValidationService
{
    private const PARSER_LENGTHS = [
        'boolean' => 1,
        'uint16' => 2,
        'int16' => 2,
        'uint32' => 4,
        'int32' => 4,
        'float32' => 4,
    ];

    public function validate(MappingProfileVersion $version): array
    {
        $version->loadMissing(['profile', 'rules.sourceUnit', 'rules.canonicalParameter', 'rules.canonicalDefinition.unit']);
        $errors = [];
        if (trim((string) $version->profile?->manufacturer) === '') {
            $errors[] = 'Profile manufacturer wajib diisi.';
        }
        if (trim((string) $version->profile?->device_model) === '') {
            $errors[] = 'Profile device model wajib diisi.';
        }
        if ($version->rules->isEmpty()) {
            $errors[] = 'Draft harus memiliki minimal satu mapping rule.';
        }

        $selectors = [];
        foreach ($version->rules as $index => $rule) {
            $prefix = 'Rule '.($index + 1).' ('.($rule->source_parameter ?: 'tanpa source').'):';
            if (trim((string) $rule->source_parameter) === '') {
                $errors[] = "{$prefix} source parameter wajib diisi.";
            }
            if (! array_key_exists($rule->parser, self::PARSER_LENGTHS) && ! in_array($rule->parser, ['decimal', 'text'], true)) {
                $errors[] = "{$prefix} parser tidak didukung.";
            }
            $expected = self::PARSER_LENGTHS[$rule->parser] ?? null;
            if ($expected !== null && (int) $rule->byte_length !== $expected) {
                $errors[] = "{$prefix} byte length harus {$expected} untuk {$rule->parser}.";
            }
            if ((int) $rule->byte_length < 1 || (int) $rule->byte_length > 1024) {
                $errors[] = "{$prefix} byte length harus antara 1 dan 1024.";
            }
            if (! in_array($rule->byte_order, ['big', 'little'], true) || ! in_array($rule->word_order, ['high_low', 'low_high'], true)) {
                $errors[] = "{$prefix} byte/word order tidak valid.";
            }
            $expectedSignedness = str_starts_with($rule->parser, 'int') ? 'signed' : (str_starts_with($rule->parser, 'uint') ? 'unsigned' : 'not_applicable');
            if ($rule->signedness !== $expectedSignedness) {
                $errors[] = "{$prefix} signedness harus {$expectedSignedness}.";
            }
            if (($rule->register_start === null) !== ($rule->register_count === null)) {
                $errors[] = "{$prefix} register start dan count harus diisi bersama.";
            } elseif ($rule->register_count !== null && ((int) $rule->byte_offset + (int) $rule->byte_length > (int) $rule->register_count * 2)) {
                $errors[] = "{$prefix} byte range melewati register bounds.";
            }
            try {
                BigDecimal::of($rule->scale);
                BigDecimal::of($rule->offset);
            } catch (Throwable) {
                $errors[] = "{$prefix} scale/offset harus decimal valid.";
            }
            if (! $rule->sourceUnit || ! $rule->sourceUnit->is_active) {
                $errors[] = "{$prefix} source unit wajib aktif dan terdaftar.";
            }
            $targetUnit = $rule->canonicalDefinition?->unit;
            if (! $rule->canonicalParameter || $rule->canonicalParameter->lifecycle !== 'active'
                || ! $rule->canonicalDefinition
                || $rule->canonicalDefinition->canonical_parameter_id !== $rule->canonical_parameter_id) {
                $errors[] = "{$prefix} canonical target/version tidak valid atau tidak aktif.";
            } elseif ($rule->sourceUnit && $targetUnit) {
                if ($rule->sourceUnit->dimension_key !== $targetUnit->dimension_key) {
                    $errors[] = "{$prefix} source dan canonical unit berbeda dimensi.";
                } elseif ($rule->source_unit_id !== $targetUnit->id && ! CanonicalUnitConversion::query()
                    ->where('source_unit_id', $rule->source_unit_id)
                    ->where('target_unit_id', $targetUnit->id)
                    ->where('is_approved', true)->exists()) {
                    $errors[] = "{$prefix} konversi unit belum disetujui.";
                }
            }
            if (! in_array($rule->origin, ['RDM', 'RDP'], true)) {
                $errors[] = "{$prefix} origin mapping harus RDM atau RDP.";
            }
            foreach ($rule->missing_markers ?? [] as $marker) {
                if (! is_string($marker) || trim($marker) === '') {
                    $errors[] = "{$prefix} missing marker harus string non-kosong.";

                    continue;
                }
                if (str_starts_with($marker, 'hex:')) {
                    $hex = substr($marker, 4);
                    if ($hex === '' || strlen($hex) % 2 !== 0 || ! ctype_xdigit($hex)) {
                        $errors[] = "{$prefix} hex missing marker tidak valid.";
                    }
                }
            }

            $selector = implode('|', [$rule->source_parameter, $rule->source_item_key, $rule->byte_offset, $rule->byte_length]);
            if (isset($selectors[$selector])) {
                $errors[] = "{$prefix} source selector ambigu dengan rule {$selectors[$selector]}.";
            } else {
                $selectors[$selector] = $index + 1;
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'checked_at' => now()->toIso8601String(),
            'rule_count' => $version->rules->count(),
        ];
    }
}
