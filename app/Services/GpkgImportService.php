<?php

namespace App\Services;

use App\Models\CorridorMonitoring;
use App\Models\ReferenceRoute;
use App\Models\SpatialInformationLayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GpkgImportService
{
    /**
     * Import a GeoPackage file as a Monitoring Corridor.
     */
    public function importCorridor(string $filePath, int $projectId, int $workspaceId): array
    {
        $features = $this->readGpkg($filePath);

        if (empty($features)) {
            return ['success' => false, 'message' => 'No features found in GPKG file.', 'count' => 0];
        }

        $imported = 0;

        DB::transaction(function () use ($features, $projectId, $workspaceId, &$imported) {
            foreach ($features as $feature) {
                $corridorCode = $feature['properties']['NAMOBJ'] ?? ('CORR-' . Str::random(6));
                $name = $feature['properties']['NAMOBJ'] ?? basename($feature['source_file'] ?? 'Unknown');
                $koridor = $feature['properties']['KORIDOR'] ?? null;
                $length = $feature['properties']['Length'] ?? null;

                // Create a ReferenceRoute for this corridor line
                $route = ReferenceRoute::updateOrCreate(
                    ['route_code' => $corridorCode],
                    [
                        'project_id' => $projectId,
                        'workspace_id' => $workspaceId,
                        'name' => $name,
                        'route_type' => 'monitoring_corridor',
                        'path_coordinates' => $feature['coordinates'],
                        'total_length' => $length,
                        'corridor_code' => $koridor,
                        'status' => 'Active',
                    ]
                );

                // Also create a CorridorMonitoring record
                CorridorMonitoring::updateOrCreate(
                    ['corridor_code' => $corridorCode],
                    [
                        'project_id' => $projectId,
                        'workspace_id' => $workspaceId,
                        'reference_route_id' => $route->id,
                        'name' => $name,
                        'path_coordinates' => $feature['coordinates'],
                        'status' => 'Active',
                        'status_metadata' => [
                            'source' => 'gpkg_import',
                            'koridor' => $koridor,
                            'length' => $length,
                            'l_koridor' => $feature['properties']['L_Koridor'] ?? null,
                        ],
                    ]
                );

                $imported++;
            }
        });

        return [
            'success' => true,
            'message' => "Imported {$imported} corridor features.",
            'count' => $imported,
        ];
    }

    /**
     * Import a GeoPackage file as an Information Layer.
     */
    public function importInformationLayer(string $filePath, int $projectId, ?int $workspaceId, ?string $layerName = null): array
    {
        $features = $this->readGpkg($filePath);

        if (empty($features)) {
            return ['success' => false, 'message' => 'No features found in GPKG file.', 'count' => 0];
        }

        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $layerCode = $layerName ? Str::slug($layerName) : Str::slug($filename);
        $displayName = $layerName ?: str_replace(['IL-', 'IL_', '_'], ['', '', ' '], $filename);

        // Detect geometry type from first feature
        $geomType = $features[0]['geometry_type'] ?? 'unknown';

        // Build GeoJSON FeatureCollection
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => array_map(function ($feature) use ($geomType) {
                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => $this->geojsonGeometryType($geomType),
                        'coordinates' => $feature['coordinates'],
                    ],
                    'properties' => $feature['properties'],
                ];
            }, $features),
        ];

        // Determine style color based on layer type
        $styleColor = $this->layerStyleColor($filename);

        $layer = SpatialInformationLayer::updateOrCreate(
            ['layer_code' => $layerCode],
            [
                'project_id' => $projectId,
                'workspace_id' => $workspaceId,
                'name' => $displayName,
                'layer_type' => $this->detectLayerType($geomType),
                'layer_payload' => $geojson,
                'style_color' => $styleColor,
                'visible_by_default' => true,
                'sort_order' => 0,
                'status' => 'Active',
            ]
        );

        return [
            'success' => true,
            'message' => "Imported information layer '{$displayName}' with " . count($features) . " features.",
            'count' => count($features),
            'layer_id' => $layer->id,
            'layer_code' => $layerCode,
        ];
    }

    /**
     * Read a GeoPackage file and extract features with coordinates.
     */
    public function readGpkg(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [];
        }

        try {
            $db = new \SQLite3($filePath, SQLITE3_OPEN_READONLY);
        } catch (\Exception $e) {
            return [];
        }

        // Get the feature table name from gpkg_contents
        $result = $db->query("SELECT table_name, data_type, srs_id FROM gpkg_contents WHERE data_type = 'features' LIMIT 1");
        if (!$result) {
            $db->close();
            return [];
        }

        $contentRow = $result->fetchArray(SQLITE3_ASSOC);
        if (!$contentRow) {
            $db->close();
            return [];
        }

        $tableName = $contentRow['table_name'];

        // Get column info
        $columns = [];
        $geomColumn = null;
        $pragmaResult = $db->query("PRAGMA table_info(\"{$tableName}\")");
        while ($col = $pragmaResult->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $col;
            $type = strtoupper($col['type']);
            if (in_array($type, ['MULTILINESTRING', 'LINESTRING', 'MULTIPOLYGON', 'POLYGON', 'POINT', 'MULTIPOINT', 'GEOMETRY'])) {
                $geomColumn = $col['name'];
            }
        }

        if (!$geomColumn) {
            // Try from gpkg_geometry_columns
            $geomResult = $db->query("SELECT column_name, geometry_type_name FROM gpkg_geometry_columns WHERE table_name = '{$tableName}' LIMIT 1");
            if ($geomResult) {
                $geomRow = $geomResult->fetchArray(SQLITE3_ASSOC);
                if ($geomRow) {
                    $geomColumn = $geomRow['column_name'];
                }
            }
        }

        if (!$geomColumn) {
            $db->close();
            return [];
        }

        // Get geometry type
        $geomTypeResult = $db->query("SELECT geometry_type_name FROM gpkg_geometry_columns WHERE table_name = '{$tableName}' LIMIT 1");
        $geomTypeName = 'GEOMETRY';
        if ($geomTypeResult) {
            $row = $geomTypeResult->fetchArray(SQLITE3_ASSOC);
            $geomTypeName = $row['geometry_type_name'] ?? 'GEOMETRY';
        }

        // Non-geometry columns
        $attrColumns = array_filter($columns, fn ($c) => $c['name'] !== $geomColumn && $c['name'] !== 'fid');
        $attrNames = array_map(fn ($c) => $c['name'], $attrColumns);
        $selectCols = implode(', ', array_map(fn ($n) => "\"{$n}\"", array_merge(['fid', $geomColumn], $attrNames)));

        $features = [];
        $dataResult = $db->query("SELECT {$selectCols} FROM \"{$tableName}\"");

        if ($dataResult) {
            while ($row = $dataResult->fetchArray(SQLITE3_ASSOC)) {
                $geomBlob = $row[$geomColumn];
                $coordinates = $this->parseGpkgGeometry($geomBlob);

                if ($coordinates === null) {
                    continue;
                }

                $properties = [];
                foreach ($attrNames as $attr) {
                    $properties[$attr] = $row[$attr];
                }

                $features[] = [
                    'fid' => $row['fid'],
                    'geometry_type' => $geomTypeName,
                    'coordinates' => $coordinates,
                    'properties' => $properties,
                    'source_file' => basename($filePath),
                ];
            }
        }

        $db->close();

        return $features;
    }

    /**
     * Parse GeoPackage standard binary geometry to coordinate arrays.
     */
    protected function parseGpkgGeometry($blob): ?array
    {
        if (!$blob || strlen($blob) < 8) {
            return null;
        }

        // GeoPackage binary header
        $flags = ord($blob[3]);
        $envelopeType = ($flags >> 1) & 0x07;
        $envelopeSizes = [0 => 0, 1 => 32, 2 => 48, 3 => 48, 4 => 64];
        $envelopeSize = $envelopeSizes[$envelopeType] ?? 0;
        $headerSize = 8 + $envelopeSize;

        $wkb = substr($blob, $headerSize);
        if (strlen($wkb) < 5) {
            return null;
        }

        return $this->parseWkb($wkb);
    }

    /**
     * Parse WKB binary to coordinate arrays.
     */
    protected function parseWkb(string $wkb, int $offset = 0): ?array
    {
        if (strlen($wkb) < $offset + 5) {
            return null;
        }

        $byteOrder = ord($wkb[$offset]);
        $fmt = $byteOrder === 1 ? 'V' : 'N'; // little or big endian for uint32
        $dblFmt = $byteOrder === 1 ? 'e' : 'E'; // little or big endian for double

        $geomType = unpack($fmt, substr($wkb, $offset + 1, 4))[1];
        $offset += 5;

        // Handle Z/M/ZM variants: type 1001-1007 = Z, 2001-2007 = M, 3001-3007 = ZM
        $hasZ = false;
        $hasM = false;
        $baseType = $geomType;
        if ($geomType > 3000 && $geomType < 4000) {
            $baseType = $geomType - 3000;
            $hasZ = true;
            $hasM = true;
        } elseif ($geomType > 2000 && $geomType < 3000) {
            $baseType = $geomType - 2000;
            $hasM = true;
        } elseif ($geomType > 1000 && $geomType < 2000) {
            $baseType = $geomType - 1000;
            $hasZ = true;
        } elseif ($geomType & 0x80000000) {
            // Alternative Z flag
            $baseType = $geomType & 0x0FFFFFFF;
            $hasZ = true;
        }

        // Bytes per coordinate: 2D=16, Z=24, M=24, ZM=32
        $coordSize = 16 + ($hasZ ? 8 : 0) + ($hasM ? 8 : 0);

        switch ($baseType) {
            case 1: // Point
                return $this->parsePoint($wkb, $offset, $dblFmt);

            case 2: // LineString
                return $this->parseLineString($wkb, $offset, $fmt, $dblFmt, $coordSize);

            case 3: // Polygon
                return $this->parsePolygon($wkb, $offset, $fmt, $dblFmt, $coordSize);

            case 4: // MultiPoint
            case 5: // MultiLineString
            case 6: // MultiPolygon
                return $this->parseMulti($wkb, $offset, $fmt);

            default:
                return null;
        }
    }

    protected function parsePoint(string $wkb, int $offset, string $dblFmt): array
    {
        $x = unpack($dblFmt, substr($wkb, $offset, 8))[1];
        $y = unpack($dblFmt, substr($wkb, $offset + 8, 8))[1];
        return [round($x, 7), round($y, 7)];
    }

    protected function parseLineString(string $wkb, int $offset, string $fmt, string $dblFmt, int $coordSize = 16): array
    {
        $numPoints = unpack($fmt, substr($wkb, $offset, 4))[1];
        $offset += 4;
        $coords = [];
        for ($i = 0; $i < $numPoints; $i++) {
            $x = unpack($dblFmt, substr($wkb, $offset, 8))[1];
            $y = unpack($dblFmt, substr($wkb, $offset + 8, 8))[1];
            $coords[] = [round($x, 7), round($y, 7)];
            $offset += $coordSize;
        }
        return $coords;
    }

    protected function parsePolygon(string $wkb, int $offset, string $fmt, string $dblFmt, int $coordSize = 16): array
    {
        $numRings = unpack($fmt, substr($wkb, $offset, 4))[1];
        $offset += 4;
        $rings = [];
        for ($r = 0; $r < $numRings; $r++) {
            $numPoints = unpack($fmt, substr($wkb, $offset, 4))[1];
            $offset += 4;
            $ring = [];
            for ($i = 0; $i < $numPoints; $i++) {
                $x = unpack($dblFmt, substr($wkb, $offset, 8))[1];
                $y = unpack($dblFmt, substr($wkb, $offset + 8, 8))[1];
                $ring[] = [round($x, 7), round($y, 7)];
                $offset += $coordSize;
            }
            $rings[] = $ring;
        }
        return $rings;
    }

    protected function parseMulti(string $wkb, int $offset, string $fmt): array
    {
        $numGeoms = unpack($fmt, substr($wkb, $offset, 4))[1];
        $offset += 4;
        $geometries = [];
        for ($i = 0; $i < $numGeoms; $i++) {
            // Each sub-geometry has its own byte order + type header
            $subResult = $this->parseWkb($wkb, $offset);
            if ($subResult !== null) {
                $geometries[] = $subResult;
            }
            // Advance offset - need to calculate consumed bytes
            $offset = $this->advanceWkbOffset($wkb, $offset);
        }
        return $geometries;
    }

    /**
     * Advance past a WKB geometry to find the next offset.
     */
    protected function advanceWkbOffset(string $wkb, int $offset): int
    {
        if (strlen($wkb) < $offset + 5) {
            return strlen($wkb);
        }

        $byteOrder = ord($wkb[$offset]);
        $fmt = $byteOrder === 1 ? 'V' : 'N';
        $geomType = unpack($fmt, substr($wkb, $offset + 1, 4))[1];
        $offset += 5;

        // Handle Z/M/ZM variants
        $hasZ = false;
        $hasM = false;
        $baseType = $geomType;
        if ($geomType > 3000 && $geomType < 4000) {
            $baseType = $geomType - 3000;
            $hasZ = true;
            $hasM = true;
        } elseif ($geomType > 2000 && $geomType < 3000) {
            $baseType = $geomType - 2000;
            $hasM = true;
        } elseif ($geomType > 1000 && $geomType < 2000) {
            $baseType = $geomType - 1000;
            $hasZ = true;
        } elseif ($geomType & 0x80000000) {
            $baseType = $geomType & 0x0FFFFFFF;
            $hasZ = true;
        }
        $coordSize = 16 + ($hasZ ? 8 : 0) + ($hasM ? 8 : 0);

        switch ($baseType) {
            case 1: // Point
                return $offset + $coordSize;

            case 2: // LineString
                $numPoints = unpack($fmt, substr($wkb, $offset, 4))[1];
                return $offset + 4 + ($numPoints * $coordSize);

            case 3: // Polygon
                $numRings = unpack($fmt, substr($wkb, $offset, 4))[1];
                $offset += 4;
                for ($r = 0; $r < $numRings; $r++) {
                    $numPoints = unpack($fmt, substr($wkb, $offset, 4))[1];
                    $offset += 4 + ($numPoints * $coordSize);
                }
                return $offset;

            case 5: // MultiLineString
            case 6: // MultiPolygon
            case 4: // MultiPoint
                $numGeoms = unpack($fmt, substr($wkb, $offset, 4))[1];
                $offset += 4;
                for ($i = 0; $i < $numGeoms; $i++) {
                    $offset = $this->advanceWkbOffset($wkb, $offset);
                }
                return $offset;

            default:
                return strlen($wkb);
        }
    }

    protected function geojsonGeometryType(string $gpkgType): string
    {
        return match (strtoupper($gpkgType)) {
            'POINT' => 'Point',
            'MULTIPOINT' => 'MultiPoint',
            'LINESTRING' => 'LineString',
            'MULTILINESTRING' => 'MultiLineString',
            'POLYGON' => 'Polygon',
            'MULTIPOLYGON' => 'MultiPolygon',
            default => 'GeometryCollection',
        };
    }

    protected function detectLayerType(string $geomType): string
    {
        $type = strtoupper($geomType);
        if (str_contains($type, 'LINE')) {
            return 'line';
        }
        if (str_contains($type, 'POLYGON')) {
            return 'polygon';
        }
        if (str_contains($type, 'POINT')) {
            return 'point';
        }
        return 'overlay';
    }

    protected function layerStyleColor(string $filename): string
    {
        $name = strtolower($filename);
        if (str_contains($name, 'sungai')) return '#2196F3';
        if (str_contains($name, 'permukiman')) return '#FF9800';
        if (str_contains($name, 'pendidikan')) return '#9C27B0';
        if (str_contains($name, 'kesehatan')) return '#F44336';
        if (str_contains($name, 'contour')) return '#795548';
        if (str_contains($name, 'batas')) return '#607D8B';
        if (str_contains($name, 'perimeter')) return '#FF5722';
        if (str_contains($name, 'puncak')) return '#E91E63';
        if (str_contains($name, 'koridor') || str_contains($name, 'corridor')) return '#1565C0';
        return '#4CAF50';
    }
}
