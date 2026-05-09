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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
            'leads.*.audit.redesign' => ['nullable', 'array'],
        ]);

        $created = DB::transaction(function () use ($leadDiscoveryJob, $validated): int {
            $created = 0;
            $seen = [];

            foreach ($validated['leads'] as $item) {
                $websiteUrl = $this->normalizeUrl($item['website_url'] ?? null);
                $sourceUrl = $this->normalizeUrl($item['source_url'] ?? null);
                $signature = $this->duplicateSignature(
                    $item['business_name'],
                    $item['city'] ?? $leadDiscoveryJob->city,
                    $item['country'] ?? $leadDiscoveryJob->country,
                    $websiteUrl,
                    $sourceUrl,
                );

                if (
                    isset($seen[$signature])
                    || $this->leadAlreadyExists(
                        $item['business_name'],
                        $item['city'] ?? $leadDiscoveryJob->city,
                        $item['country'] ?? $leadDiscoveryJob->country,
                        $websiteUrl,
                        $sourceUrl,
                    )
                ) {
                    continue;
                }

                $seen[$signature] = true;

                $lead = Lead::create([
                    'business_name' => $item['business_name'],
                    'category' => $item['category'] ?? $leadDiscoveryJob->niche,
                    'city' => $item['city'] ?? $leadDiscoveryJob->city,
                    'country' => $item['country'] ?? $leadDiscoveryJob->country,
                    'website_url' => $websiteUrl,
                    'source' => $item['source'] ?? 'lead_discovery',
                    'source_url' => $sourceUrl,
                    'phone' => $item['phone'] ?? null,
                    'email' => $item['email'] ?? null,
                    'status' => isset($item['audit']) ? Lead::STATUS_NEEDS_REVIEW : Lead::STATUS_NEW,
                    'notes' => $item['notes'] ?? null,
                ]);

                $auditPayload = $item['audit'] ?? null;
                if ($auditPayload) {
                    $scores = $auditPayload['scores'] ?? [];
                    $screenshots = $auditPayload['screenshots'] ?? [];

                    $storedScreenshots = $this->storeAuditAssets($screenshots, 'screenshots');
                    $redesign = $auditPayload['redesign'] ?? [];
                    $storedRedesign = $this->storeAuditAssets($redesign, 'redesigns');

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
                        'redesign_concept_json' => $redesign['concept'] ?? [],
                        'desktop_screenshot_path' => $storedScreenshots['desktop_file'] ?? $screenshots['desktop_file'] ?? null,
                        'mobile_screenshot_path' => $storedScreenshots['mobile_file'] ?? $screenshots['mobile_file'] ?? null,
                        'redesign_mockup_path' => $storedRedesign['html_file'] ?? $redesign['html_file'] ?? null,
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

    private function leadAlreadyExists(string $businessName, ?string $city, ?string $country, ?string $websiteUrl, ?string $sourceUrl): bool
    {
        return Lead::query()
            ->where(function ($query) use ($businessName, $city, $country, $websiteUrl, $sourceUrl): void {
                if ($websiteUrl) {
                    $query->orWhereIn(DB::raw('LOWER(website_url)'), $this->urlVariants($websiteUrl));
                }

                if ($sourceUrl) {
                    $query->orWhereIn(DB::raw('LOWER(source_url)'), $this->urlVariants($sourceUrl));
                }

                $query->orWhere(function ($query) use ($businessName, $city, $country): void {
                    $query->whereRaw('LOWER(business_name) = ?', [mb_strtolower(trim($businessName))])
                        ->whereRaw("LOWER(COALESCE(city, '')) = ?", [mb_strtolower(trim((string) $city))])
                        ->whereRaw("LOWER(COALESCE(country, '')) = ?", [mb_strtolower(trim((string) $country))]);
                });
            })
            ->exists();
    }

    private function duplicateSignature(string $businessName, ?string $city, ?string $country, ?string $websiteUrl, ?string $sourceUrl): string
    {
        if ($websiteUrl) {
            return 'website:'.$this->urlKey($websiteUrl);
        }

        if ($sourceUrl) {
            return 'source:'.$this->urlKey($sourceUrl);
        }

        return 'business:'.implode('|', [
            mb_strtolower(trim($businessName)),
            mb_strtolower(trim((string) $city)),
            mb_strtolower(trim((string) $country)),
        ]);
    }

    private function normalizeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return rtrim($url, '/');
        }

        $scheme = mb_strtolower($parts['scheme'] ?? 'https');
        $host = mb_strtolower($parts['host']);
        $path = isset($parts['path']) ? '/'.ltrim($parts['path'], '/') : '';
        $path = $path === '/' ? '' : rtrim($path, '/');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return "{$scheme}://{$host}{$path}{$query}";
    }

    /**
     * @return array<int, string>
     */
    private function urlVariants(string $url): array
    {
        $lower = mb_strtolower(rtrim($url, '/'));
        $variants = [$lower, $lower.'/'];

        foreach (['https://', 'http://'] as $scheme) {
            if (str_starts_with($lower, $scheme)) {
                $otherScheme = $scheme === 'https://' ? 'http://' : 'https://';
                $withoutScheme = substr($lower, strlen($scheme));
                $variants[] = $otherScheme.$withoutScheme;
                $variants[] = $otherScheme.$withoutScheme.'/';
            }
        }

        return array_values(array_unique($variants));
    }

    private function urlKey(string $url): string
    {
        $key = mb_strtolower(rtrim($url, '/'));

        return preg_replace('#^https?://(www\.)?#', '', $key) ?? $key;
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

    /**
     * @return array<string, string>
     */
    private function storeAuditAssets(array $payload, string $directory): array
    {
        $stored = [];
        $publicDirectory = public_path($directory);
        File::ensureDirectoryExists($publicDirectory);

        foreach ([
            'desktop_base64' => ['desktop_file', 'png'],
            'mobile_base64' => ['mobile_file', 'png'],
            'desktop_jpeg_base64' => ['desktop_file', 'jpg'],
            'mobile_jpeg_base64' => ['mobile_file', 'jpg'],
            'html_base64' => ['html_file', 'html'],
        ] as $sourceKey => [$targetKey, $extension]) {
            if (empty($payload[$sourceKey]) || ! is_string($payload[$sourceKey])) {
                continue;
            }

            $raw = preg_replace('/^data:[^;]+;base64,/', '', $payload[$sourceKey]);
            $bytes = base64_decode($raw, true);
            if ($bytes === false) {
                continue;
            }

            $filename = Str::uuid().'.'.$extension;
            File::put($publicDirectory.DIRECTORY_SEPARATOR.$filename, $bytes);
            $stored[$targetKey] = "/{$directory}/{$filename}";
        }

        return $stored;
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
