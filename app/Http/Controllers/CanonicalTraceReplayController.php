<?php

namespace App\Http\Controllers;

use App\Models\CanonicalReplayBatch;
use App\Models\CanonicalValue;
use App\Models\DataLogger;
use App\Models\MappingProfileVersion;
use App\Models\RawIngestionEvent;
use App\Models\Sensor;
use App\Services\Replay\CanonicalReplayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CanonicalTraceReplayController extends Controller
{
    public function __construct(private readonly CanonicalReplayService $replay) {}

    public function index(Request $request): View
    {
        $raw = $request->filled('raw_event_id') ? RawIngestionEvent::query()->find($request->integer('raw_event_id')) : null;
        $value = $request->filled('canonical_value_id') ? CanonicalValue::query()->find($request->integer('canonical_value_id')) : null;

        return view('modules.canonical-trace.index', [
            'raw' => $raw, 'value' => $value,
            'batches' => CanonicalReplayBatch::query()->with('version.profile')->latest()->limit(100)->get(),
            'versions' => MappingProfileVersion::query()->with('profile')->where('status', 'published')->latest()->get(),
            'dataLoggers' => DataLogger::query()->orderBy('logger_code')->get(),
            'sensors' => Sensor::query()->orderBy('sensor_code')->get(),
        ]);
    }

    public function raw(RawIngestionEvent $event): View
    {
        $event->load(['items', 'canonicalValues.run', 'canonicalValues.rule', 'canonicalValues.mappingVersion.profile', 'canonicalValues.parameter', 'canonicalValues.unit']);

        return view('modules.canonical-trace.show', ['raw' => $event, 'value' => null, 'batch' => null]);
    }

    public function value(CanonicalValue $value): View
    {
        $value->load(['rawEvent.items', 'rawItem', 'run', 'rule', 'mappingVersion.profile', 'parameter', 'unit', 'observation']);

        return view('modules.canonical-trace.show', ['raw' => null, 'value' => $value, 'batch' => null]);
    }

    public function create(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'source' => ['required', 'regex:/^(data_logger|sensor):[1-9][0-9]*$/'],
            'observed_from' => ['required', 'date'], 'observed_to' => ['required', 'date'],
            'mapping_profile_version_id' => ['required', 'exists:mapping_profile_versions,id'],
            'reason' => ['required', 'string', 'max:1000'], 'max_events' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);
        [$data['source_type'], $data['source_id']] = explode(':', $data['source']);
        try {
            $batch = $this->replay->create($data, $request->user()?->id);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['replay' => $e->getMessage()]);
        }

        return redirect()->route('canonical-trace.replays.show', $batch)->with('status', 'Replay draft dibuat. Jalankan dry-run.');
    }

    public function batch(CanonicalReplayBatch $batch): View
    {
        $batch->load(['version.profile', 'items.event']);

        return view('modules.canonical-trace.show', ['raw' => null, 'value' => null, 'batch' => $batch]);
    }

    public function dryRun(CanonicalReplayBatch $batch): RedirectResponse
    {
        try {
            $summary = $this->replay->dryRun($batch);
        } catch (Throwable $e) {
            return back()->withErrors(['replay' => $e->getMessage()]);
        }

        return back()->with('status', 'Dry-run selesai: '.json_encode($summary));
    }

    public function execute(CanonicalReplayBatch $batch): RedirectResponse
    {
        try {
            $this->replay->execute($batch);
        } catch (Throwable $e) {
            return back()->withErrors(['replay' => $e->getMessage()]);
        }

        return back()->with('status', 'Replay execution selesai atau checkpointed.');
    }
}
