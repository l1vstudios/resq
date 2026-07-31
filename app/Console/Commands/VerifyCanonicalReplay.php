<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifyCanonicalReplay extends Command
{
    protected $signature = 'canonical:verify-replay';

    protected $description = 'Run the canonical live-path verifier including deterministic replay scenarios';

    public function handle(): int
    {
        return $this->call('canonical:verify-live-path');
    }
}
