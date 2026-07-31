<?php

namespace App\Console\Commands;

use App\Models\RawIngestionEvent;
use Illuminate\Console\Command;

class InspectRawIngestion extends Command
{
    protected $signature = 'canonical:raw:inspect
        {identifier : Numeric raw event ID or logical event key}
        {--source-type= : Required with a non-numeric logical event key}
        {--source-id= : Required with a non-numeric logical event key}
        {--hex-preview : Show at most the first 64 evidence bytes as hexadecimal}';

    protected $description = 'Inspect one immutable raw ingestion event without exposing full payload evidence';

    public function handle(): int
    {
        $identifier = (string) $this->argument('identifier');
        $query = RawIngestionEvent::query()->with(['items' => fn ($items) => $items->limit(100)]);

        if (ctype_digit($identifier)) {
            $query->whereKey((int) $identifier);
        } else {
            $sourceType = trim((string) $this->option('source-type'));
            $sourceId = trim((string) $this->option('source-id'));

            if ($sourceType === '' || ! ctype_digit($sourceId) || (int) $sourceId < 1) {
                $this->error('A logical event key requires --source-type and a positive numeric --source-id.');

                return self::FAILURE;
            }

            $query
                ->where('source_type', $sourceType)
                ->where('source_id', (int) $sourceId)
                ->where('logical_event_key', $identifier);
        }

        $event = $query->first();
        if (! $event) {
            $this->error('Raw ingestion event was not found.');

            return self::FAILURE;
        }

        $this->table(['Field', 'Value'], [
            ['ID', $event->id],
            ['Event key', $event->logical_event_key],
            ['Source', $event->source_type . ':' . $event->source_id],
            ['Project / station / logger / sensor', implode(' / ', [
                $event->project_id ?? '-',
                $event->monitoring_station_id ?? '-',
                $event->data_logger_id ?? '-',
                $event->sensor_id ?? '-',
            ])],
            ['Transport / envelope', $event->transport . ' / ' . $event->envelope_version],
            ['Classification', $event->payload_classification],
            ['Payload SHA-256', $event->payload_hash],
            ['Payload bytes', $event->payload_size],
            ['Observed at', $event->observed_at?->toIso8601String() ?? '-'],
            ['Observed provenance / fallback', $event->observed_at_provenance . ' / ' . ($event->observed_at_fallback ? 'yes' : 'no')],
            ['Received at', $event->received_at?->toIso8601String() ?? '-'],
            ['Processed at', $event->processed_at?->toIso8601String() ?? '-'],
            ['Receipt / processing status', $event->receipt_status . ' / ' . ($event->processing_status ?? '-')],
            ['Failure reason', $event->failure_reason ? mb_strimwidth($event->failure_reason, 0, 240, '…') : '-'],
        ]);

        $sourceSnapshot = $this->redact($event->source_snapshot ?? []);
        if ($sourceSnapshot !== []) {
            $this->newLine();
            $this->line('Source snapshot (preserved at receipt):');
            $this->line(json_encode($sourceSnapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
        }

        $inspection = $this->redact($event->inspection_payload ?? []);
        if ($inspection !== []) {
            $this->newLine();
            $this->line('Inspection copy (redacted):');
            $this->line(json_encode($inspection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
        }

        if ($this->option('hex-preview')) {
            $this->newLine();
            $this->warn('Evidence preview is limited to 64 bytes.');
            $this->line(bin2hex(substr((string) $event->getRawOriginal('payload'), 0, 64)));
        }

        $this->newLine();
        $this->table(
            ['Item', 'Parameter', 'Raw value', 'Raw SHA-256', 'Register', 'Status', 'Reason'],
            $event->items->map(fn ($item) => [
                $item->item_key,
                $item->source_parameter ?? '-',
                $item->raw_value !== null ? mb_strimwidth($item->raw_value, 0, 80, '…') : '-',
                $item->raw_hash ?? '-',
                $item->register_address ?? '-',
                $item->status,
                $item->reason ? mb_strimwidth($item->reason, 0, 100, '…') : '-',
            ])->all(),
        );

        if ($event->items->count() >= 100) {
            $this->warn('Item output was limited to 100 rows.');
        }

        return self::SUCCESS;
    }

    private function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $sensitiveKeys = ['authorization', 'token', 'device_token', 'password', 'secret'];
        $redacted = [];

        foreach ($value as $key => $item) {
            $redacted[$key] = in_array(strtolower((string) $key), $sensitiveKeys, true)
                ? '[REDACTED]'
                : $this->redact($item);
        }

        return $redacted;
    }
}
