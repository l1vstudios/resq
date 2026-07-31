<?php

namespace App\Http\Controllers;

use App\Models\CanonicalParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CanonicalCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $domain = strtolower(trim((string) $request->query('domain')));
        $lifecycle = strtolower(trim((string) $request->query('lifecycle')));
        $search = trim((string) $request->query('q'));
        $domain = in_array($domain, ['meteorology', 'hydrology', 'geotechnical'], true) ? $domain : '';
        $lifecycle = in_array($lifecycle, ['active', 'deprecated'], true) ? $lifecycle : '';

        if (! Schema::hasTable('canonical_parameters') || ! Schema::hasTable('canonical_parameter_versions')) {
            return view('modules.canonical-catalog.index', [
                'parameters' => collect(),
                'summary' => ['meteorology' => 0, 'hydrology' => 0, 'geotechnical' => 0, 'active' => 0, 'deprecated' => 0],
                'filters' => compact('domain', 'lifecycle', 'search'),
                'catalogUnavailable' => true,
            ]);
        }

        $query = CanonicalParameter::query()->with('definition.unit');
        if ($domain !== '') {
            $query->where('domain', $domain);
        }
        if ($lifecycle !== '') {
            $query->where('lifecycle', $lifecycle);
        }
        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($nested) use ($escaped) {
                $nested->where('key', 'like', "%{$escaped}%")
                    ->orWhereHas('definition', fn ($definition) => $definition
                        ->where('display_name', 'like', "%{$escaped}%")
                        ->orWhere('definition', 'like', "%{$escaped}%"));
            });
        }

        $domainCounts = CanonicalParameter::query()->selectRaw('domain, COUNT(*) as aggregate')->groupBy('domain')->pluck('aggregate', 'domain');
        $lifecycleCounts = CanonicalParameter::query()->selectRaw('lifecycle, COUNT(*) as aggregate')->groupBy('lifecycle')->pluck('aggregate', 'lifecycle');

        return view('modules.canonical-catalog.index', [
            'parameters' => $query->orderBy('domain')->orderBy('key')->get(),
            'summary' => [
                'meteorology' => (int) ($domainCounts['meteorology'] ?? 0),
                'hydrology' => (int) ($domainCounts['hydrology'] ?? 0),
                'geotechnical' => (int) ($domainCounts['geotechnical'] ?? 0),
                'active' => (int) ($lifecycleCounts['active'] ?? 0),
                'deprecated' => (int) ($lifecycleCounts['deprecated'] ?? 0),
            ],
            'filters' => compact('domain', 'lifecycle', 'search'),
            'catalogUnavailable' => false,
        ]);
    }
}
