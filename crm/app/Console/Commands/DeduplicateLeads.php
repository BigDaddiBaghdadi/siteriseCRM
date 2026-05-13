<?php

namespace App\Console\Commands;

use App\Models\Lead;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class DeduplicateLeads extends Command
{
    protected $signature = 'leads:deduplicate {--dry-run : Show duplicates without deleting them}';

    protected $description = 'Delete duplicate leads, keeping the strongest lead record in each duplicate group.';

    public function handle(): int
    {
        /** @var Collection<int, Lead> $leads */
        $leads = Lead::query()
            ->with('latestAudit')
            ->withCount(['audits', 'auditJobs'])
            ->oldest()
            ->get();

        $parents = [];
        $leadKeys = [];
        $seenKeyOwner = [];

        foreach ($leads as $lead) {
            $parents[$lead->id] = $lead->id;
            $leadKeys[$lead->id] = $this->duplicateKeys($lead);

            foreach ($leadKeys[$lead->id] as $key) {
                if (isset($seenKeyOwner[$key])) {
                    $this->union($parents, $lead->id, $seenKeyOwner[$key]);
                } else {
                    $seenKeyOwner[$key] = $lead->id;
                }
            }
        }

        $groups = [];
        foreach ($leads as $lead) {
            if ($leadKeys[$lead->id] === []) {
                continue;
            }

            $groups[$this->find($parents, $lead->id)][] = $lead;
        }

        $duplicateGroups = array_filter($groups, fn (array $group): bool => count($group) > 1);

        if ($duplicateGroups === []) {
            $this->info('No duplicate leads found.');
            return self::SUCCESS;
        }

        $deleteIds = [];
        foreach ($duplicateGroups as $group) {
            usort($group, fn (Lead $a, Lead $b): int => $this->rankLead($b) <=> $this->rankLead($a));
            $keeper = array_shift($group);

            $this->line("Keep #{$keeper->id}: {$keeper->business_name}");
            foreach ($group as $duplicate) {
                $deleteIds[] = $duplicate->id;
                $this->line("  delete #{$duplicate->id}: {$duplicate->business_name}");
            }
        }

        $this->info(count($duplicateGroups).' duplicate groups found; '.count($deleteIds).' leads to delete.');

        if ($this->option('dry-run')) {
            $this->warn('Dry run only. No leads deleted.');
            return self::SUCCESS;
        }

        Lead::query()->whereIn('id', $deleteIds)->delete();

        $this->info(count($deleteIds).' duplicate leads deleted.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function duplicateKeys(Lead $lead): array
    {
        $keys = [];

        if ($lead->website_url) {
            $keys[] = 'website:'.$this->urlKey($lead->website_url);
        }

        if ($lead->source_url) {
            $keys[] = 'source:'.$this->urlKey($lead->source_url);
        }

        $business = Str::of($lead->business_name)->trim()->lower()->squish()->toString();
        if ($business !== '') {
            $keys[] = 'business:'.implode('|', [
                $business,
                Str::of((string) $lead->city)->trim()->lower()->squish()->toString(),
                Str::of((string) $lead->country)->trim()->lower()->squish()->toString(),
            ]);
        }

        return array_values(array_unique($keys));
    }

    private function rankLead(Lead $lead): int
    {
        $hasScreenshot = $lead->latestAudit?->desktop_screenshot_path ? 1 : 0;
        $hasWebsite = $lead->website_url ? 1 : 0;
        $hasContact = ($lead->email || $lead->phone) ? 1 : 0;
        $ageScore = max(0, 1_000_000 - $lead->id);

        return ($lead->audits_count * 1_000_000)
            + ($lead->audit_jobs_count * 100_000)
            + ($hasScreenshot * 10_000)
            + ($hasWebsite * 1_000)
            + ($hasContact * 100)
            + $ageScore;
    }

    private function urlKey(string $url): string
    {
        $key = Str::of($url)->trim()->lower()->rtrim('/')->toString();
        $key = preg_replace('#^https?://(www\.)?#', '', $key) ?? $key;

        return $key;
    }

    /**
     * @param array<int, int> $parents
     */
    private function find(array &$parents, int $id): int
    {
        if ($parents[$id] !== $id) {
            $parents[$id] = $this->find($parents, $parents[$id]);
        }

        return $parents[$id];
    }

    /**
     * @param array<int, int> $parents
     */
    private function union(array &$parents, int $a, int $b): void
    {
        $rootA = $this->find($parents, $a);
        $rootB = $this->find($parents, $b);

        if ($rootA !== $rootB) {
            $parents[$rootB] = $rootA;
        }
    }
}
