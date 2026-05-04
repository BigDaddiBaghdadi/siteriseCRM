<?php

namespace App\Console\Commands;

use App\Models\WorkerToken;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateWorkerToken extends Command
{
    protected $signature = 'worker:token {name : Human-readable worker name}';

    protected $description = 'Create an API token for a portable audit worker.';

    public function handle(): int
    {
        $plainToken = Str::random(48);

        WorkerToken::create([
            'name' => $this->argument('name'),
            'token_hash' => WorkerToken::hashToken($plainToken),
            'active' => true,
        ]);

        $this->info('Worker token created. Store it now; it will not be shown again.');
        $this->line($plainToken);

        return self::SUCCESS;
    }
}

