<?php

namespace App\Http\Controllers;

use App\Models\IngressRolloutEvidence;
use App\Models\IngressRolloutState;
use App\Services\Ingestion\IngressRolloutService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CanonicalIngressRolloutController extends Controller
{
    private const TRANSITIONS = [
        'expand' => ['shadow'],
        'shadow' => ['verified', 'rolled_back'],
        'verified' => ['cutover', 'rolled_back'],
        'cutover' => ['rolled_back'],
        'rolled_back' => ['shadow'],
    ];

    public function __construct(private readonly IngressRolloutService $rollout) {}

    public function index(Request $request): View
    {
        $paths = array_values(config('canonical.ingress_rollout.paths', []));
        $states = array_values(config('canonical.ingress_rollout.states', []));
        $available = $this->tablesAvailable();
        $defaultTo = CarbonImmutable::now('UTC');
        $defaultFrom = $defaultTo->subDay();
        $maxRows = (int) config('canonical.ingress_rollout.evidence_max_rows', 1000);
        $data = $request->validate([
            'path' => ['nullable', Rule::in($paths)],
            'from_utc' => ['nullable', 'date'],
            'to_utc' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$maxRows],
        ]);
        $path = (string) ($data['path'] ?? ($paths[0] ?? ''));
        $fromUtc = CarbonImmutable::parse($data['from_utc'] ?? $defaultFrom)->utc();
        $toUtc = CarbonImmutable::parse($data['to_utc'] ?? $defaultTo)->utc();
        $limit = (int) ($data['limit'] ?? min(100, $maxRows));
        $maxHours = (int) config('canonical.ingress_rollout.evidence_max_window_hours', 168);
        if ($toUtc->lessThan($fromUtc) || $fromUtc->diffInHours($toUtc) > $maxHours) {
            throw ValidationException::withMessages([
                'to_utc' => "Rentang evidence harus berurutan dan tidak melebihi {$maxHours} jam.",
            ]);
        }
        $rolloutStates = collect();
        $selectedState = null;
        $summary = [];
        $evidence = collect();

        if ($available && $path !== '') {
            $rolloutStates = IngressRolloutState::query()
                ->with(['actor', 'transitions.actor', 'attestations.actor'])
                ->orderBy('path_key')
                ->get();
            $selectedState = $rolloutStates->firstWhere('path_key', $path);
            $summary = $this->rollout->evidenceSummary($path, $fromUtc, $toUtc);
            $evidence = IngressRolloutEvidence::query()
                ->where('path_key', $path)
                ->whereBetween('recorded_at', [$fromUtc, $toUtc])
                ->latest('recorded_at')
                ->latest('id')
                ->limit($limit)
                ->get();
        }

        return view('modules.canonical-ingress-rollout.index', [
            'available' => $available,
            'paths' => $paths,
            'states' => $states,
            'rolloutStates' => $rolloutStates,
            'selectedState' => $selectedState,
            'path' => $path,
            'fromUtc' => $fromUtc,
            'toUtc' => $toUtc,
            'limit' => $limit,
            'summary' => $summary,
            'evidence' => $evidence,
            'allowedTransitions' => self::TRANSITIONS,
            'suiteVersion' => $this->rollout->verificationSuiteVersion(),
            'attestationFreshAfter' => CarbonImmutable::now('UTC')->subHours(
                (int) config('canonical.ingress_rollout.attestation_freshness_hours', 24)
            ),
        ]);
    }

    public function transition(Request $request): RedirectResponse
    {
        $paths = array_values(config('canonical.ingress_rollout.paths', []));
        $states = array_values(config('canonical.ingress_rollout.states', []));
        $data = $request->validate([
            'path' => ['required', Rule::in($paths)],
            'target_state' => ['required', Rule::in($states)],
            'reason' => ['required', 'string', 'max:'.(int) config('canonical.ingress_rollout.reason_max_length', 500)],
        ]);
        $actor = Auth::user();

        if (! $actor) {
            abort(401);
        }

        try {
            $this->rollout->transition(
                $data['path'],
                $data['target_state'],
                (int) $actor->getAuthIdentifier(),
                $data['reason'],
            );
        } catch (Throwable $exception) {
            return back()->withInput()->withErrors(['transition' => $exception->getMessage()]);
        }

        return redirect()
            ->route('canonical-ingress-rollout.index', ['path' => $data['path']])
            ->with('status', "Rollout {$data['path']} berpindah ke {$data['target_state']}.");
    }

    private function tablesAvailable(): bool
    {
        return collect([
            'ingress_rollout_states',
            'ingress_rollout_transitions',
            'ingress_rollout_evidence',
            'ingress_verification_attestations',
        ])->every(fn (string $table): bool => Schema::hasTable($table));
    }
}
