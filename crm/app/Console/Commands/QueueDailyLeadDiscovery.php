<?php

namespace App\Console\Commands;

use App\Models\LeadDiscoveryJob;
use Illuminate\Console\Command;

class QueueDailyLeadDiscovery extends Command
{
    protected $signature = 'lead-discovery:queue-daily
        {--city=Sofia : City to search}
        {--country=Bulgaria : Country to search}
        {--niche=Dentists : Niche to search}
        {--limit=5 : Number of leads to find}';

    protected $description = 'Queue a small manual lead discovery job for real websites needing redesign.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        if (! in_array($limit, LeadDiscoveryJob::RESULT_LIMITS, true)) {
            $this->error('Limit must be one of: '.implode(', ', LeadDiscoveryJob::RESULT_LIMITS));
            return self::FAILURE;
        }

        $job = LeadDiscoveryJob::create([
            'niche' => (string) $this->option('niche'),
            'random_niche' => false,
            'city' => (string) $this->option('city'),
            'country' => (string) $this->option('country'),
            'result_limit' => $limit,
            'target' => LeadDiscoveryJob::TARGET_NEEDS_REDESIGN,
            'status' => LeadDiscoveryJob::STATUS_QUEUED,
            'metadata_json' => [
                'queued_by' => 'manual_command',
                'real_websites_only' => true,
            ],
        ]);

        $this->info("Queued manual lead discovery job #{$job->id}.");

        return self::SUCCESS;
    }
}
