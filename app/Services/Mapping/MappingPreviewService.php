<?php

namespace App\Services\Mapping;

use App\Models\MappingRule;
use App\Models\RawIngestionItem;
use App\Services\Canonicalization\DeterministicTransformer;
use App\Services\Canonicalization\TransformationRequest;
use App\Services\Canonicalization\TransformationResult;
use InvalidArgumentException;

final class MappingPreviewService
{
    public function __construct(private readonly DeterministicTransformer $transformer) {}

    public function preview(MappingRule $rule, array $input): TransformationResult
    {
        $rule->loadMissing(['version.profile', 'sourceUnit', 'canonicalParameter', 'canonicalDefinition.unit']);
        [$raw, $inputMode] = $this->resolveInput($input);

        return $this->transformer->transform(new TransformationRequest(
            raw: $raw,
            inputMode: $inputMode,
            parser: $rule->parser,
            byteOffset: $inputMode === 'binary' ? (int) $rule->byte_offset : 0,
            length: $inputMode === 'binary' ? (int) $rule->byte_length : null,
            byteOrder: $rule->byte_order,
            wordOrder: $rule->word_order,
            scale: $rule->scale,
            offset: $rule->offset,
            sourceUnitCode: $rule->sourceUnit?->code,
            targetUnitCode: $rule->canonicalDefinition?->unit?->code,
            missingMarkers: $rule->missing_markers ?? [],
            canonicalParameterKey: $rule->canonicalParameter?->key ?? '',
            outputPrecision: (int) ($rule->canonicalDefinition?->output_precision ?? 2),
            roundingMode: $rule->canonicalDefinition?->rounding_mode ?? 'half_up',
            origin: $rule->origin,
            mappingVersionIdentity: $rule->version->profile->profile_key.'/v'.$rule->version->version.'/rule-'.$rule->id,
            runMode: 'preview',
            targetDataType: $rule->canonicalDefinition?->data_type ?? 'decimal',
        ));
    }

    private function resolveInput(array $input): array
    {
        if (! empty($input['raw_item_id'])) {
            $item = RawIngestionItem::query()->findOrFail((int) $input['raw_item_id']);
            $bytes = $item->getRawOriginal('raw_bytes');

            return $bytes !== null ? [(string) $bytes, 'binary'] : [(string) $item->raw_value, 'text'];
        }

        $format = $input['sample_format'] ?? 'text';
        $sample = (string) ($input['sample'] ?? '');
        if ($format === 'hex') {
            $hex = preg_replace('/\s+/', '', $sample);
            if ($hex === '' || strlen($hex) % 2 !== 0 || ! ctype_xdigit($hex)) {
                throw new InvalidArgumentException('Sample hex tidak valid.');
            }

            return [hex2bin($hex), 'binary'];
        }
        if ($format !== 'text' || $sample === '') {
            throw new InvalidArgumentException('Sample text wajib diisi.');
        }

        return [$sample, 'text'];
    }
}
