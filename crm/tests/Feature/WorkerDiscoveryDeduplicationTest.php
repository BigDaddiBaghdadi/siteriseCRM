<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadDiscoveryJob;
use App\Models\WorkerToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerDiscoveryDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_discovery_result_skips_duplicate_leads(): void
    {
        $token = 'test-worker-token';
        WorkerToken::create([
            'name' => 'test-worker',
            'token_hash' => WorkerToken::hashToken($token),
            'active' => true,
        ]);

        Lead::create([
            'business_name' => 'Existing Dental',
            'category' => 'Dentists',
            'city' => 'Sofia',
            'country' => 'Bulgaria',
            'website_url' => 'https://existing.example',
            'source' => 'lead_discovery',
            'source_url' => 'https://osm.example/node/1',
            'status' => Lead::STATUS_NEW,
        ]);

        $job = LeadDiscoveryJob::create([
            'niche' => 'Dentists',
            'random_niche' => false,
            'city' => 'Sofia',
            'country' => 'Bulgaria',
            'result_limit' => 5,
            'target' => LeadDiscoveryJob::TARGET_NEEDS_REDESIGN,
            'status' => LeadDiscoveryJob::STATUS_RUNNING,
        ]);

        $response = $this->withToken($token)->postJson("/api/worker/discovery-jobs/{$job->id}/result", [
            'leads' => [
                [
                    'business_name' => 'Existing Dental',
                    'category' => 'Dentists',
                    'city' => 'Sofia',
                    'country' => 'Bulgaria',
                    'website_url' => 'https://existing.example/',
                    'source_url' => 'https://osm.example/node/1',
                ],
                [
                    'business_name' => 'Fresh Dental',
                    'category' => 'Dentists',
                    'city' => 'Sofia',
                    'country' => 'Bulgaria',
                    'website_url' => 'fresh.example/',
                    'source_url' => 'https://osm.example/node/2',
                ],
                [
                    'business_name' => 'Fresh Dental Duplicate',
                    'category' => 'Dentists',
                    'city' => 'Sofia',
                    'country' => 'Bulgaria',
                    'website_url' => 'https://fresh.example',
                    'source_url' => 'https://osm.example/node/2',
                ],
            ],
        ]);

        $response->assertCreated()->assertJson([
            'leads_created' => 1,
        ]);

        $this->assertDatabaseCount('leads', 2);
        $this->assertDatabaseHas('leads', [
            'business_name' => 'Fresh Dental',
            'website_url' => 'https://fresh.example',
        ]);
        $this->assertSame(1, $job->refresh()->leads_found);
    }
}
