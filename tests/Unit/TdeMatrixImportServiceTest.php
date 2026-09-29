<?php

namespace Tests\Unit;

use App\Services\TdeMatrixImportService;
use InvalidArgumentException;
use Tests\TestCase;

class TdeMatrixImportServiceTest extends TestCase
{
    public function test_it_parses_complete_tde_matrix_rows(): void
    {
        $service = new TdeMatrixImportService;
        $rows = collect($service->templateRows())
            ->map(function (array $row, int $index) {
                if ($index === 0) {
                    return $row;
                }

                $row[5] = 'projection';
                $row[6] = 'Diagnosis '.$index;
                $row[7] = 'Generated test row';
                $row[8] = 'high';
                $row[9] = 'TDE_'.$index;

                return $row;
            })
            ->all();

        $parsed = $service->parseMatrixRows($rows);

        $this->assertCount(243, $parsed['matrix']);
        $this->assertSame(243, $parsed['summary']['row_count']);
        $this->assertArrayHasKey('<<<<<', $parsed['matrix']);
        $this->assertArrayHasKey('>>>>>', $parsed['matrix']);
    }

    public function test_it_rejects_duplicate_or_incomplete_matrix_patterns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate combination pattern');

        $service = new TdeMatrixImportService;
        $rows = [
            ['AT', 'RH', 'DP', 'DPS', 'AP', 'summary'],
            ['<', '<', '<', '<', '<', 'First'],
            ['<', '<', '<', '<', '<', 'Duplicate'],
        ];

        $service->parseMatrixRows($rows);
    }
}
