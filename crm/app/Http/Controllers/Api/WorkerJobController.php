<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditJob;
use App\Models\Lead;
use App\Models\WorkerToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class WorkerJobController extends Controller
{
    public function next(Request $request): JsonResponse|Response
    {
        $worker = $this->authenticateWorker($request);
        if (! $worker) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $job = DB::transaction(function () use ($worker): ?AuditJob {
            $job = AuditJob::query()
                ->where('status', AuditJob::STATUS_QUEUED)
                ->with('lead')
                ->orderBy('priority')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (! $job) {
                return null;
            }

            $job->update([
                'status' => AuditJob::STATUS_AUDITING,
                'attempts' => $job->attempts + 1,
                'locked_by' => $worker->name,
                'locked_at' => now(),
                'last_error' => null,
            ]);

            $job->lead->update([
                'status' => Lead::STATUS_AUDITING,
            ]);

            return $job->refresh()->load('lead');
        });

        if (! $job) {
            return response()->noContent();
        }

        return response()->json([
            'job_id' => (string) $job->id,
            'lead_id' => (string) $job->lead->id,
            'business_name' => $job->lead->business_name,
            'website_url' => $job->lead->website_url,
            'category' => $job->lead->category,
            'city' => $job->lead->city,
        ]);
    }

    public function result(Request $request, AuditJob $auditJob): JsonResponse
    {
        $worker = $this->authenticateWorker($request);
        if (! $worker) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($auditJob->status !== AuditJob::STATUS_AUDITING) {
            return response()->json(['message' => 'Audit job is not locked for auditing'], 409);
        }

        $validated = $request->validate([
            'lead_id' => ['required'],
            'business_summary' => ['nullable', 'string'],
            'scores' => ['required', 'array'],
            'scores.overall' => ['nullable', 'integer', 'between:0,100'],
            'scores.redesign' => ['nullable', 'integer', 'between:0,100'],
            'scores.mobile' => ['nullable', 'integer', 'between:0,100'],
            'scores.performance' => ['nullable', 'integer', 'between:0,100'],
            'scores.accessibility' => ['nullable', 'integer', 'between:0,100'],
            'scores.seo' => ['nullable', 'integer', 'between:0,100'],
            'issues' => ['nullable', 'array'],
            'recommendations' => ['nullable', 'array'],
            'contact' => ['nullable', 'array'],
            'technology' => ['nullable', 'array'],
            'screenshots' => ['nullable', 'array'],
            'screenshots.desktop_file' => ['nullable', 'string'],
            'screenshots.mobile_file' => ['nullable', 'string'],
        ]);

        if ((string) $validated['lead_id'] !== (string) $auditJob->lead_id) {
            return response()->json(['message' => 'Result lead_id does not match audit job'], 422);
        }

        $audit = DB::transaction(function () use ($auditJob, $validated): Audit {
            $scores = $validated['scores'];
            $screenshots = $validated['screenshots'] ?? [];

            $audit = Audit::create([
                'lead_id' => $auditJob->lead_id,
                'audit_job_id' => $auditJob->id,
                'business_summary' => $validated['business_summary'] ?? null,
                'overall_score' => $scores['overall'] ?? null,
                'redesign_score' => $scores['redesign'] ?? null,
                'mobile_score' => $scores['mobile'] ?? null,
                'performance_score' => $scores['performance'] ?? null,
                'accessibility_score' => $scores['accessibility'] ?? null,
                'seo_score' => $scores['seo'] ?? null,
                'issues_json' => $validated['issues'] ?? [],
                'recommendations_json' => $validated['recommendations'] ?? [],
                'technology_json' => $validated['technology'] ?? [],
                'contact_json' => $validated['contact'] ?? [],
                'desktop_screenshot_path' => $screenshots['desktop_file'] ?? null,
                'mobile_screenshot_path' => $screenshots['mobile_file'] ?? null,
            ]);

            $auditJob->update([
                'status' => AuditJob::STATUS_AUDITED,
                'last_error' => null,
            ]);

            $auditJob->lead()->update([
                'status' => Lead::STATUS_NEEDS_REVIEW,
            ]);

            return $audit;
        });

        return response()->json([
            'audit_id' => (string) $audit->id,
            'status' => 'stored',
        ], 201);
    }

    public function fail(Request $request, AuditJob $auditJob): JsonResponse
    {
        $worker = $this->authenticateWorker($request);
        if (! $worker) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'error' => ['required', 'string', 'max:4000'],
            'retryable' => ['required', 'boolean'],
        ]);

        $nextStatus = $validated['retryable'] && $auditJob->attempts < 3
            ? AuditJob::STATUS_QUEUED
            : AuditJob::STATUS_FAILED;

        DB::transaction(function () use ($auditJob, $nextStatus, $validated): void {
            $auditJob->update([
                'status' => $nextStatus,
                'locked_by' => null,
                'locked_at' => null,
                'last_error' => $validated['error'],
            ]);

            if ($nextStatus === AuditJob::STATUS_FAILED) {
                $auditJob->lead()->update([
                    'status' => Lead::STATUS_AUDIT_FAILED,
                ]);
            }
        });

        return response()->json([
            'status' => $nextStatus,
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

