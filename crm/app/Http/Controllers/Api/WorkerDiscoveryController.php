<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\Lead;
use App\Models\LeadDiscoveryJob;
use App\Models\WorkerToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class WorkerDiscoveryController extends Controller
{
    public function next(Request $request): JsonResponse|Response
    {
        $worker = $this->authenticateWorker($request);
        if (! $worker) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $job = DB::transaction(function () use ($worker): ?LeadDiscoveryJob {
            $job = LeadDiscoveryJob::query()
                ->where('status', LeadDiscoveryJob::STATUS_QUEUED)
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (! $job) {
                return null;
            }

            $job->update([
                'status' => LeadDiscoveryJob::STATUS_RUNNING,
                'attempts' => $job->attempts + 1,
                'locked_by' => $worker->name,
                'locked_at' => now(),
                'last_error' => null,
            ]);

            return $job->refresh();
        });

        if (! $job) {
            return response()->noContent();
        }

        return response()->json([
            'job_id' => (string) $job->id,
            'niche' => $job->niche,
            'random_niche' => $job->random_niche,
            'city' => $job->city,
            'country' => $job->country,
            'result_limit' => $job->result_limit,
            'target' => $job->target,
        ]);
    }

    public function result(Request $request, LeadDiscoveryJob $leadDiscoveryJob): JsonResponse
    {
        $worker = $this->authenticateWorker($request);
        if (! $worker) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($leadDiscoveryJob->status !== LeadDiscoveryJob::STATUS_RUNNING) {
            return response()->json(['message' => 'Discovery job is not running'], 409);
        }

        $validated = $request->validate([
            'leads' => ['required', 'array', 'max:50'],
            'leads.*.business_name' => ['required', 'string', 'max:255'],
            'leads.*.category' => ['nullable', 'string', 'max:255'],
            'leads.*.city' => ['nullable', 'string', 'max:255'],
            'leads.*.country' => ['nullable', 'string', 'max:255'],
            'leads.*.website_url' => ['nullable', 'string', 'max:2048'],
            'leads.*.source' => ['nullable', 'string', 'max:255'],
            'leads.*.source_url' => ['nullable', 'string', 'max:2048'],
            'leads.*.phone' => ['nullable', 'string', 'max:255'],
            'leads.*.email' => ['nullable', 'string', 'max:255'],
            'leads.*.notes' => ['nullable', 'string'],
            'leads.*.audit' => ['nullable', 'array'],
            'leads.*.audit.business_summary' => ['nullable', 'string'],
            'leads.*.audit.scores' => ['nullable', 'array'],
            'leads.*.audit.issues' => ['nullable', 'array'],
            'leads.*.audit.recommendations' => ['nullable', 'array'],
            'leads.*.audit.contact' => ['nullable', 'array'],
            'leads.*.audit.technology' => ['nullable', 'array'],
            'leads.*.audit.screenshots' => ['nullable', 'array'],
        ]);

        $created = DB::transaction(function () use ($leadDiscoveryJob, $validated): int {
            $created = 0;

            foreach ($validated['leads'] as $item) {
                $lead = Lead::create([
                    'business_name' => $item['business_name'],
                    'category' => $item['category'] ?? $leadDiscoveryJob->niche,
                    'city' => $item['city'] ?? $leadDiscoveryJob->city,
                    'country' => $item['country'] ?? $leadDiscoveryJob->country,
                    'website_url' => $item['website_url'] ?? null,
                    'source' => $item['source'] ?? 'lead_discovery',
                    'source_url' => $item['source_url'] ?? null,
                    'phone' => $item['phone'] ?? null,
                    'email' => $item['email'] ?? null,
                    'status' => isset($item['audit']) ? Lead::STATUS_NEEDS_REVIEW : Lead::STATUS_NEW,
                    'notes' => $item['notes'] ?? null,
                ]);

                $auditPayload = $item['audit'] ?? null;
                if ($auditPayload) {
                    $scores = $auditPayload['scores'] ?? [];
                    $screenshots = $auditPayload['screenshots'] ?? [];

                    Audit::create([
                        'lead_id' => $lead->id,
                        'audit_job_id' => null,
                        'business_summary' => $auditPayload['business_summary'] ?? null,
                        'overall_score' => $scores['overall'] ?? null,
                        'redesign_score' => $scores['redesign'] ?? null,
                        'mobile_score' => $scores['mobile'] ?? null,
                        'performance_score' => $scores['performance'] ?? null,
                        'accessibility_score' => $scores['accessibility'] ?? null,
                        'seo_score' => $scores['seo'] ?? null,
                        'issues_json' => $auditPayload['issues'] ?? [],
                        'recommendations_json' => $auditPayload['recommendations'] ?? [],
                        'technology_json' => $auditPayload['technology'] ?? [],
                        'contact_json' => $auditPayload['contact'] ?? [],
                        'desktop_screenshot_path' => $screenshots['desktop_file'] ?? null,
                        'mobile_screenshot_path' => $screenshots['mobile_file'] ?? null,
                    ]);
                }

                $created++;
            }

            $leadDiscoveryJob->update([
                'status' => LeadDiscoveryJob::STATUS_COMPLETED,
                'completed_at' => now(),
                'leads_found' => $created,
                'last_error' => null,
            ]);

            return $created;
        });

        return response()->json([
            'status' => LeadDiscoveryJob::STATUS_COMPLETED,
            'leads_created' => $created,
        ], 201);
    }

    public function fail(Request $request, LeadDiscoveryJob $leadDiscoveryJob): JsonResponse
    {
        $worker = $this->authenticateWorker($request);
        if (! $worker) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'error' => ['required', 'string', 'max:4000'],
            'retryable' => ['required', 'boolean'],
        ]);

        $leadDiscoveryJob->update([
            'status' => $validated['retryable'] && $leadDiscoveryJob->attempts < 3
                ? LeadDiscoveryJob::STATUS_QUEUED
                : LeadDiscoveryJob::STATUS_FAILED,
            'locked_by' => null,
            'locked_at' => null,
            'last_error' => $validated['error'],
        ]);

        return response()->json([
            'status' => $leadDiscoveryJob->status,
        ]);
    }

    private function authenticateWorker(Request $request): ?WorkerToken
    {
        $plainToken = $request->bearerToken();
        if (! $plainToken) {
            return null;
        }

        $worker = WorkerToken::query()
            ->where('token_hash', WorkerToken::hashToken($plainToken))
            ->where('active', true)
            ->first();

        if ($worker) {
            $worker->forceFill(['last_used_at' => now()])->save();
        }

        return $worker;
    }
}
