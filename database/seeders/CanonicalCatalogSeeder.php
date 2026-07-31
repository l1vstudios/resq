<?php

namespace Database\Seeders;

use App\Models\CanonicalParameter;
use App\Models\CanonicalParameterVersion;
use App\Models\CanonicalUnit;
use App\Models\CanonicalUnitConversion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CanonicalCatalogSeeder extends Seeder
{
    private const SOURCE_DOCUMENT = 'D&A 016. Canonical Data & Database (July 2026)';

    public function run(): void
    {
        DB::transaction(function () {
            $units = $this->seedUnits();
            $this->seedConversions($units);
            $this->seedParameters($units);
        });
    }

    /** @return array<string, CanonicalUnit> */
    private function seedUnits(): array
    {
        $definitions = [
            ['code' => 'celsius', 'symbol' => '°C', 'name' => 'degree Celsius', 'dimension_key' => 'temperature'],
            ['code' => 'kelvin', 'symbol' => 'K', 'name' => 'kelvin', 'dimension_key' => 'temperature'],
            ['code' => 'fahrenheit', 'symbol' => '°F', 'name' => 'degree Fahrenheit', 'dimension_key' => 'temperature'],
            ['code' => 'percent_relative_humidity', 'symbol' => '%RH', 'name' => 'percent relative humidity', 'dimension_key' => 'relative_humidity'],
            ['code' => 'pascal', 'symbol' => 'Pa', 'name' => 'pascal', 'dimension_key' => 'pressure'],
            ['code' => 'hectopascal', 'symbol' => 'hPa', 'name' => 'hectopascal', 'dimension_key' => 'pressure'],
            ['code' => 'kilopascal', 'symbol' => 'kPa', 'name' => 'kilopascal', 'dimension_key' => 'pressure'],
            ['code' => 'metre_per_second', 'symbol' => 'm/s', 'name' => 'metre per second', 'dimension_key' => 'speed'],
            ['code' => 'kilometre_per_hour', 'symbol' => 'km/h', 'name' => 'kilometre per hour', 'dimension_key' => 'speed'],
            ['code' => 'degree_angle', 'symbol' => '°', 'name' => 'degree angle', 'dimension_key' => 'angle'],
            ['code' => 'millimetre_per_hour', 'symbol' => 'mm/h', 'name' => 'millimetre per hour', 'dimension_key' => 'precipitation_rate'],
            ['code' => 'millimetre', 'symbol' => 'mm', 'name' => 'millimetre', 'dimension_key' => 'length'],
            ['code' => 'metre', 'symbol' => 'm', 'name' => 'metre', 'dimension_key' => 'length'],
            ['code' => 'watt_per_square_metre', 'symbol' => 'W/m²', 'name' => 'watt per square metre', 'dimension_key' => 'irradiance'],
            ['code' => 'lux', 'symbol' => 'lx', 'name' => 'lux', 'dimension_key' => 'illuminance'],
            ['code' => 'cubic_metre_per_second', 'symbol' => 'm³/s', 'name' => 'cubic metre per second', 'dimension_key' => 'volumetric_flow_rate'],
            ['code' => 'litre_per_second', 'symbol' => 'L/s', 'name' => 'litre per second', 'dimension_key' => 'volumetric_flow_rate'],
            ['code' => 'cubic_metre', 'symbol' => 'm³', 'name' => 'cubic metre', 'dimension_key' => 'volume'],
            ['code' => 'litre', 'symbol' => 'L', 'name' => 'litre', 'dimension_key' => 'volume'],
            ['code' => 'percent_vwc', 'symbol' => '%VWC', 'name' => 'percent volumetric water content', 'dimension_key' => 'soil_water_content'],
            ['code' => 'decisiemens_per_metre', 'symbol' => 'dS/m', 'name' => 'decisiemens per metre', 'dimension_key' => 'electrical_conductivity'],
        ];

        $units = [];
        foreach ($definitions as $definition) {
            $units[$definition['code']] = CanonicalUnit::query()->updateOrCreate(
                ['code' => $definition['code']],
                $definition + ['is_active' => true, 'definition' => 'Curated canonical/device unit for deterministic mapping.']
            );
        }

        return $units;
    }

    /** @param array<string, CanonicalUnit> $units */
    private function seedConversions(array $units): void
    {
        $pairs = [
            ['kelvin', 'celsius', '1', '-273.15'], ['celsius', 'kelvin', '1', '273.15'],
            ['fahrenheit', 'celsius', '0.55555555555555555556', '-17.777777777777777778'],
            ['celsius', 'fahrenheit', '1.8', '32'],
            ['pascal', 'hectopascal', '0.01', '0'], ['hectopascal', 'pascal', '100', '0'],
            ['pascal', 'kilopascal', '0.001', '0'], ['kilopascal', 'pascal', '1000', '0'],
            ['hectopascal', 'kilopascal', '0.1', '0'], ['kilopascal', 'hectopascal', '10', '0'],
            ['kilometre_per_hour', 'metre_per_second', '0.27777777777777777778', '0'],
            ['metre_per_second', 'kilometre_per_hour', '3.6', '0'],
            ['millimetre', 'metre', '0.001', '0'], ['metre', 'millimetre', '1000', '0'],
            ['litre_per_second', 'cubic_metre_per_second', '0.001', '0'],
            ['cubic_metre_per_second', 'litre_per_second', '1000', '0'],
            ['litre', 'cubic_metre', '0.001', '0'], ['cubic_metre', 'litre', '1000', '0'],
        ];

        foreach ($pairs as [$source, $target, $multiplier, $offset]) {
            CanonicalUnitConversion::query()->updateOrCreate(
                ['source_unit_id' => $units[$source]->id, 'target_unit_id' => $units[$target]->id],
                ['multiplier' => $multiplier, 'offset' => $offset, 'is_approved' => true, 'approval_reference' => self::SOURCE_DOCUMENT]
            );
        }
    }

    /** @param array<string, CanonicalUnit> $units */
    private function seedParameters(array $units): void
    {
        foreach ($this->parameterDefinitions() as $definition) {
            $parameter = CanonicalParameter::query()->firstOrCreate(
                ['key' => $definition['key']],
                ['domain' => $definition['domain'], 'lifecycle' => 'active', 'current_version' => 1]
            );

            CanonicalParameterVersion::query()->firstOrCreate(
                ['canonical_parameter_id' => $parameter->id, 'version' => 1],
                [
                    'display_name' => str($definition['key'])->replace('_', ' ')->title()->toString(),
                    'definition' => $definition['definition'],
                    'canonical_unit_id' => $units[$definition['unit']]->id,
                    'data_type' => 'decimal',
                    'measurement_characteristic' => $definition['characteristic'],
                    'output_precision' => $definition['precision'],
                    'rounding_mode' => 'half_up',
                    'source_document' => self::SOURCE_DOCUMENT,
                    'source_reference' => $definition['reference'],
                    'effective_at' => now(),
                    'metadata' => ['allowed_origins' => $definition['origins']],
                ]
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function parameterDefinitions(): array
    {
        $m = 'meteorology';
        $h = 'hydrology';
        $g = 'geotechnical';
        $rdm = ['RDM'];
        $rdp = ['RDP'];
        $ppc = ['PPC'];

        return [
            $this->p('air_temperature', $m, 'Temperatur udara pada waktu observasi.', 'celsius', 'instantaneous', 2, $rdm, 'Lampiran A.1'),
            $this->p('relative_humidity', $m, 'Kelembapan relatif udara.', 'percent_relative_humidity', 'instantaneous', 2, $rdm, 'Lampiran A.2'),
            $this->p('atmospheric_pressure', $m, 'Tekanan atmosfer pada lokasi pengamatan.', 'hectopascal', 'instantaneous', 2, $rdm, 'Lampiran A.3'),
            $this->p('wind_speed', $m, 'Kecepatan angin pada waktu observasi.', 'metre_per_second', 'instantaneous', 2, $rdm, 'Lampiran A.4'),
            $this->p('wind_direction', $m, 'Arah datangnya angin terhadap arah utara.', 'degree_angle', 'directional', 1, $rdm, 'Lampiran A.5'),
            $this->p('wind_gust', $m, 'Kecepatan angin tertinggi yang dihitung perangkat dalam interval pengukuran.', 'metre_per_second', 'interval_maximum', 2, $rdp, 'Lampiran A.6'),
            $this->p('rainfall_intensity', $m, 'Intensitas hujan yang dihitung perangkat berdasarkan pembacaan curah hujan dan interval waktu.', 'millimetre_per_hour', 'rate', 2, $rdp, 'Lampiran A.7'),
            $this->p('rainfall_accumulation', $m, 'Akumulasi curah hujan yang dihitung perangkat.', 'millimetre', 'accumulation', 2, $rdp, 'Lampiran A.8'),
            $this->p('solar_radiation', $m, 'Radiasi matahari yang diterima per satuan luas.', 'watt_per_square_metre', 'instantaneous', 2, $rdm, 'Lampiran A.9'),
            $this->p('illuminance', $m, 'Tingkat pencahayaan pada lokasi pengamatan.', 'lux', 'instantaneous', 2, $rdm, 'Lampiran A.10'),
            $this->p('dew_point', $m, 'Temperatur titik embun yang dihitung perangkat dari temperatur dan kelembapan udara.', 'celsius', 'derived', 2, $rdp, 'Lampiran A.11'),
            $this->p('dew_point_spread', $m, 'Selisih antara air_temperature dan dew_point pada waktu observasi yang sama.', 'celsius', 'derived', 2, $ppc, 'Lampiran B.1'),
            $this->p('distance_to_water_surface', $h, 'Jarak dari titik referensi sensor ke permukaan air.', 'metre', 'instantaneous', 3, $rdm, 'Lampiran C.1'),
            $this->p('water_level', $h, 'Tinggi muka air terhadap titik acuan.', 'metre', 'level', 3, ['RDP', 'PPC'], 'Lampiran C.2/D.1'),
            $this->p('water_depth', $h, 'Kedalaman kolom air terhadap dasar atau acuan penampang.', 'metre', 'depth', 3, $rdp, 'Lampiran C.3'),
            $this->p('flow_velocity', $h, 'Kecepatan aliran air pada waktu observasi.', 'metre_per_second', 'instantaneous', 3, $rdm, 'Lampiran C.4'),
            $this->p('discharge', $h, 'Volume aliran per satuan waktu.', 'cubic_metre_per_second', 'rate', 3, ['RDP', 'PPC'], 'Lampiran C.5/D.3'),
            $this->p('total_flow', $h, 'Akumulasi volume aliran yang dihitung perangkat.', 'cubic_metre', 'accumulation', 3, $rdp, 'Lampiran C.6'),
            $this->p('water_temperature', $h, 'Temperatur air pada lokasi pengamatan.', 'celsius', 'instantaneous', 2, $rdm, 'Lampiran C.7'),
            $this->p('water_surface_elevation', $h, 'Elevasi permukaan air dari elevasi referensi sensor dan jarak ke permukaan air.', 'metre', 'derived', 3, $ppc, 'Lampiran D.2'),
            $this->p('soil_moisture', $g, 'Kandungan air volumetrik dalam tanah pada titik dan kedalaman pengukuran.', 'percent_vwc', 'instantaneous', 2, $rdm, 'Lampiran E.1'),
            $this->p('soil_temperature', $g, 'Temperatur tanah pada titik dan kedalaman pengukuran.', 'celsius', 'instantaneous', 2, $rdm, 'Lampiran E.2'),
            $this->p('soil_electrical_conductivity', $g, 'Kemampuan tanah menghantarkan arus listrik.', 'decisiemens_per_metre', 'instantaneous', 3, $rdm, 'Lampiran E.3'),
            $this->p('pore_water_pressure', $g, 'Tekanan air di dalam pori tanah atau batuan.', 'kilopascal', 'instantaneous', 3, $rdm, 'Lampiran E.4'),
            $this->p('soil_suction', $g, 'Tekanan negatif atau daya isap air di dalam tanah.', 'kilopascal', 'instantaneous', 3, $rdm, 'Lampiran E.5'),
            $this->p('tilt_x', $g, 'Sudut kemiringan sensor terhadap sumbu X.', 'degree_angle', 'directional', 4, $rdm, 'Lampiran E.6'),
            $this->p('tilt_y', $g, 'Sudut kemiringan sensor terhadap sumbu Y.', 'degree_angle', 'directional', 4, $rdm, 'Lampiran E.7'),
            $this->p('borehole_tilt_x', $g, 'Sudut kemiringan bawah permukaan terhadap sumbu X.', 'degree_angle', 'directional', 4, $rdm, 'Lampiran E.8'),
            $this->p('borehole_tilt_y', $g, 'Sudut kemiringan bawah permukaan terhadap sumbu Y.', 'degree_angle', 'directional', 4, $rdm, 'Lampiran E.9'),
            $this->p('ground_displacement', $g, 'Besaran perpindahan tanah terhadap titik referensi.', 'millimetre', 'displacement', 3, $rdm, 'Lampiran E.10'),
            $this->p('crack_width', $g, 'Lebar bukaan retakan pada titik pengamatan.', 'millimetre', 'displacement', 3, $rdm, 'Lampiran E.11'),
            $this->p('tilt_magnitude', $g, 'Besaran kemiringan resultan dari tilt_x dan tilt_y pada waktu observasi yang sama.', 'degree_angle', 'derived', 4, $ppc, 'Lampiran F.1'),
            $this->p('borehole_tilt_magnitude', $g, 'Besaran kemiringan bawah permukaan dari borehole_tilt_x dan borehole_tilt_y.', 'degree_angle', 'derived', 4, $ppc, 'Lampiran F.2'),
        ];
    }

    /** @param array<int, string> $origins */
    private function p(string $key, string $domain, string $definition, string $unit, string $characteristic, int $precision, array $origins, string $reference): array
    {
        return compact('key', 'domain', 'definition', 'unit', 'characteristic', 'precision', 'origins', 'reference');
    }
}
