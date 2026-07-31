<?php

namespace App\Console\Commands;

use App\Models\CanonicalParameter;
use App\Models\CanonicalParameterVersion;
use App\Models\CanonicalUnit;
use App\Models\CanonicalUnitConversion;
use App\Services\Canonicalization\DeterministicTransformer;
use App\Services\Canonicalization\NamedPpcRegistry;
use App\Services\Canonicalization\TransformationRequest;
use App\Services\Canonicalization\UnitConverter;
use Database\Seeders\CanonicalCatalogSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class VerifyCanonicalTransformation extends Command
{
    protected $signature = 'canonical:verify-core {--seed : Idempotently seed the canonical catalog before verification}';

    protected $description = 'Run independent catalog and raw-to-canonical golden-vector integration checks';

    public function handle(): int
    {
        if (! Schema::hasTable('canonical_parameters')) {
            $this->error('Canonical tables do not exist. Run migrations first.');

            return self::FAILURE;
        }

        if ($this->option('seed')) {
            $this->call('db:seed', ['--class' => CanonicalCatalogSeeder::class, '--force' => true]);
            $before = $this->counts();
            $this->call('db:seed', ['--class' => CanonicalCatalogSeeder::class, '--force' => true]);
            $this->check('catalog reseed is idempotent', $before === $this->counts());
        }

        $this->check('PDF catalog has 33 stable parameters', CanonicalParameter::count() === 33 && CanonicalParameterVersion::count() === 33);
        $this->check('unit registry is populated', CanonicalUnit::count() >= 21 && CanonicalUnitConversion::count() >= 18);

        $transformer = new DeterministicTransformer(new UnitConverter);
        $vectors = [
            ['uint16 scale', $this->request(pack('n', 302), 'binary', 'uint16', 2, 'big', 'high_low', '0.1', '0', 'celsius', 'celsius'), 'value', '30.20'],
            ['int16 little endian', $this->request(hex2bin('85ff'), 'binary', 'int16', 2, 'little', 'high_low', '0.1', '0', 'celsius', 'celsius'), 'value', '-12.30'],
            ['uint32 low-high words', $this->request(hex2bin('86a00001'), 'binary', 'uint32', 4, 'big', 'low_high', '1', '0', null, null, precision: 0), 'value', '100000'],
            ['affine Fahrenheit to Celsius', $this->request('86', 'text', 'decimal', 2, 'big', 'high_low', '1', '0', 'fahrenheit', 'celsius'), 'value', '30.00'],
            ['genuine zero', $this->request('0', 'text', 'decimal', 1, 'big', 'high_low', '1', '0', null, null), 'value', '0.00'],
            ['missing marker', $this->request(hex2bin('ffff'), 'binary', 'uint16', 2, 'big', 'high_low', '1', '0', null, null, ['hex:ffff']), 'missing', null],
            ['IEEE non-finite', $this->request(hex2bin('7f800000'), 'binary', 'float32', 4, 'big', 'high_low', '1', '0', null, null), 'non_finite', null],
            ['invalid strict text', $this->request('12x', 'text', 'decimal', 3, 'big', 'high_low', '1', '0', null, null), 'invalid', null],
            ['bounded exponent input', $this->request('1e1000000000', 'text', 'decimal', 12, 'big', 'high_low', '1', '0', null, null), 'overflow', null],
            ['incompatible dimension', $this->request('20', 'text', 'decimal', 2, 'big', 'high_low', '1', '0', 'celsius', 'metre'), 'conversion_failure', null],
            ['unmapped target', $this->request('20', 'text', 'decimal', 2, 'big', 'high_low', '1', '0', null, null, parameter: ''), 'unmapped', null],
        ];

        foreach ($vectors as [$name, $request, $status, $value]) {
            $result = $transformer->transform($request);
            $this->check($name, $result->status === $status && ($value === null || $result->value === $value), $result->status.':'.($result->value ?? $result->reason));
        }

        $deterministic = $this->request(pack('n', 302), 'binary', 'uint16', 2, 'big', 'high_low', '0.1', '0', 'celsius', 'celsius');
        $first = $transformer->transform($deterministic);
        $second = $transformer->transform($deterministic);
        $this->check('same request gives same fingerprint, value, and trace', $first->toArray() === $second->toArray());

        $ppc = new NamedPpcRegistry;
        $this->check('named PPC computes approved handler', $ppc->calculate('dew_point_spread', ['air_temperature' => '30.2', 'dew_point' => '24.7']) === '5.5');
        $this->check('device semantic value suppresses PPC', $ppc->calculate('dew_point_spread', ['air_temperature' => '30.2', 'dew_point' => '24.7'], ['dew_point_spread']) === null);
        try {
            $ppc->calculate('arbitrary_formula', []);
            $this->check('arbitrary PPC formula is rejected', false);
        } catch (\InvalidArgumentException) {
            $this->check('arbitrary PPC formula is rejected', true);
        }

        $this->newLine();
        $this->info(sprintf('Canonical core verification: %d passed, %d failed.', $this->passed, $this->failed));

        return $this->failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private int $passed = 0;

    private int $failed = 0;

    private function check(string $label, bool $condition, ?string $actual = null): void
    {
        if ($condition) {
            $this->line("<info>PASS</info> {$label}");
            $this->passed++;

            return;
        }
        $this->line("<error>FAIL</error> {$label}".($actual ? " ({$actual})" : ''));
        $this->failed++;
    }

    private function counts(): array
    {
        return [CanonicalUnit::count(), CanonicalUnitConversion::count(), CanonicalParameter::count(), CanonicalParameterVersion::count()];
    }

    private function request(
        string $raw,
        string $inputMode,
        string $parser,
        int $length,
        string $byteOrder,
        string $wordOrder,
        string $scale,
        string $offset,
        ?string $sourceUnit,
        ?string $targetUnit,
        array $missing = [],
        string $parameter = 'air_temperature',
        int $precision = 2,
    ): TransformationRequest {
        return new TransformationRequest(
            raw: $raw,
            inputMode: $inputMode,
            parser: $parser,
            byteOffset: 0,
            length: $length,
            byteOrder: $byteOrder,
            wordOrder: $wordOrder,
            scale: $scale,
            offset: $offset,
            sourceUnitCode: $sourceUnit,
            targetUnitCode: $targetUnit,
            missingMarkers: $missing,
            canonicalParameterKey: $parameter,
            outputPrecision: $precision,
            roundingMode: 'half_up',
            origin: 'RDM',
            mappingVersionIdentity: 'golden-vector/v1',
            runMode: 'preview',
        );
    }
}
