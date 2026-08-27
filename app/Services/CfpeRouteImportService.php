<?php

namespace App\Services;

use App\Models\ReferencePoint;
use App\Models\ReferenceRoute;
use Illuminate\Support\Facades\DB;

class CfpeRouteImportService
{
    /**
     * Import CFPE route data from a semicolon-delimited CSV file.
     *
     * @param string $filePath Path to the CSV file
     * @param int $projectId
     * @param int $workspaceId
     * @return array Summary of import results
     */
    public function import(string $filePath, int $projectId, ?int $workspaceId): array
    {
        $rows = $this->parseCsv($filePath);

        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'No data rows found in CSV file.',
                'routes_count' => 0,
                'points_count' => 0,
            ];
        }

        $groupedByRoute = collect($rows)->groupBy('Route');

        $routesCount = 0;
        $pointsCount = 0;

        DB::transaction(function () use ($groupedByRoute, $projectId, $workspaceId, &$routesCount, &$pointsCount) {
            foreach ($groupedByRoute as $routeCode => $routeRows) {
                $firstRow = $routeRows->first();

                // Create or update the ReferenceRoute
                $pathCoordinates = $routeRows
                    ->filter(fn ($row) => !empty($row['Long']) && !empty($row['Lat']))
                    ->map(fn ($row) => [(float) $row['Long'], (float) $row['Lat']])
                    ->values()
                    ->toArray();

                $segmentData = $this->buildSegmentData($routeRows);

                $route = ReferenceRoute::updateOrCreate(
                    [
                        'route_code' => $routeCode,
                    ],
                    [
                        'project_id' => $projectId,
                        'workspace_id' => $workspaceId,
                        'name' => $routeCode,
                        'route_type' => 'cfpe_corridor',
                        'total_length' => $this->parseNumeric($firstRow['Length'] ?? null),
                        'corridor_code' => $firstRow['KORIDOR'] ?? null,
                        'path_coordinates' => $pathCoordinates,
                        'segment_data' => $segmentData,
                        'status' => 'Active',
                    ]
                );

                $routesCount++;

                // Create or update ReferencePoints for each row
                foreach ($routeRows as $row) {
                    $cfpeId = trim($row['ID_CFPE'] ?? '');
                    $bmId = trim($row['BM_ID'] ?? '');
                    $chainage = $this->parseChainage($row['Chaniage'] ?? '');

                    ReferencePoint::updateOrCreate(
                        [
                            'point_code' => $cfpeId,
                        ],
                        [
                            'project_id' => $projectId,
                            'workspace_id' => $workspaceId,
                            'reference_route_id' => $route->id,
                            'name' => !empty($bmId) ? $bmId : $cfpeId,
                            'point_type' => 'BM',
                            'chainage' => $chainage,
                            'segment_name' => $row['NAMOBJ'] ?? null,
                            'corridor_code' => $row['KORIDOR'] ?? null,
                            'distance_in_segment' => $this->parseNumeric($row['distance'] ?? null),
                            'bm_id' => $bmId ?: null,
                            'cfpe_id' => $cfpeId ?: null,
                            'latitude' => !empty($row['Lat']) ? (float) $row['Lat'] : null,
                            'longitude' => !empty($row['Long']) ? (float) $row['Long'] : null,
                            'status' => 'Active',
                        ]
                    );

                    $pointsCount++;
                }
            }
        });

        return [
            'success' => true,
            'message' => "Successfully imported {$routesCount} routes and {$pointsCount} reference points.",
            'routes_count' => $routesCount,
            'points_count' => $pointsCount,
        ];
    }

    /**
     * Parse the semicolon-delimited CSV file.
     *
     * @param string $filePath
     * @return array
     */
    protected function parseCsv(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        $rows = [];
        $headers = null;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $fields = str_getcsv($line, ';');

            if ($headers === null) {
                $headers = array_map('trim', $fields);
                continue;
            }

            if (count($fields) !== count($headers)) {
                continue;
            }

            $row = array_combine($headers, array_map('trim', $fields));
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Parse chainage value, handling commas and special dash value.
     *
     * @param string $value
     * @return float
     */
    protected function parseChainage(string $value): float
    {
        $value = trim($value);

        // Handle special dash value ' -   ' which means 0
        if ($value === '' || preg_match('/^\s*-\s*$/', $value)) {
            return 0.0;
        }

        // Remove commas used as thousands separator (e.g., '1,000.00')
        $value = str_replace(',', '', $value);

        return (float) $value;
    }

    /**
     * Parse a numeric value, removing commas if present.
     *
     * @param string|null $value
     * @return float|null
     */
    protected function parseNumeric(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        $value = str_replace(',', '', $value);

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Build segment data grouped by NAMOBJ.
     *
     * @param \Illuminate\Support\Collection $routeRows
     * @return array
     */
    protected function buildSegmentData($routeRows): array
    {
        $segments = [];

        $grouped = $routeRows->groupBy('NAMOBJ');

        foreach ($grouped as $segmentName => $segmentRows) {
            $points = $segmentRows->map(function ($row) {
                return [
                    'cfpe_id' => $row['ID_CFPE'] ?? null,
                    'bm_id' => $row['BM_ID'] ?? null,
                    'chainage' => $this->parseChainage($row['Chaniage'] ?? ''),
                    'distance' => $this->parseNumeric($row['distance'] ?? null),
                    'latitude' => !empty($row['Lat']) ? (float) $row['Lat'] : null,
                    'longitude' => !empty($row['Long']) ? (float) $row['Long'] : null,
                ];
            })->values()->toArray();

            $segments[] = [
                'segment_name' => $segmentName,
                'corridor' => $segmentRows->first()['KORIDOR'] ?? null,
                'points_count' => count($points),
                'points' => $points,
            ];
        }

        return $segments;
    }
}
