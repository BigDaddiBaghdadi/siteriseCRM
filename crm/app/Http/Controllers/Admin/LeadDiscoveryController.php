<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadDiscoveryJob;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadDiscoveryController extends Controller
{
    public function index(): View
    {
        $leads = Lead::with('latestAudit')
            ->latest()
            ->limit(24)
            ->get()
            ->map(function (Lead $lead): Lead {
                $audit = $lead->latestAudit;
                $contact = $audit?->contact_json ?? [];

                $issues = collect($audit?->issues_json ?? [])->filter();
                $recommendations = collect($audit?->recommendations_json ?? [])->filter();

                if ($audit && $issues->count() < 5) {
                    $issues = $issues
                        ->merge($audit->redesign_score !== null && $audit->redesign_score >= 70 ? ['High redesign score means the site likely has a visible opportunity for a stronger first impression.'] : [])
                        ->merge(empty($contact['emails'] ?? []) ? ['Worker did not find a public email, so contact capture may be weak.'] : [])
                        ->merge(empty($contact['contact_page'] ?? null) ? ['Contact path is not obvious enough for a cold visitor.'] : [])
                        ->merge(empty($audit->technology_json['analytics'] ?? false) ? ['No obvious analytics tracking found, so the business may not measure leads properly.'] : [])
                        ->unique()
                        ->values();
                }

                if ($audit && $recommendations->count() < 5) {
                    $recommendations = $recommendations
                        ->merge(['Make the hero section clearer with one strong offer and one primary call button.'])
                        ->merge(['Add visible trust proof: reviews, certifications, client logos, or local credibility signals.'])
                        ->merge(['Create a cleaner service section that is easy to scan on mobile.'])
                        ->merge(['Add conversion tracking for calls, forms, and booking clicks.'])
                        ->unique()
                        ->values();
                }

                $lead->setAttribute('discovery_issues', $issues->take(6)->values());
                $lead->setAttribute('discovery_recommendations', $recommendations->take(5)->values());
                $lead->setAttribute('discovery_contact', $contact);
                $lead->setAttribute(
                    'discovery_emails',
                    collect([$lead->email])->merge($contact['emails'] ?? [])->filter()->unique()->values(),
                );

                return $lead;
            });

        return view('admin.lead-discovery.index', [
            'jobs' => LeadDiscoveryJob::latest()->limit(12)->get(),
            'leads' => $leads,
            'limits' => LeadDiscoveryJob::RESULT_LIMITS,
            'targets' => [
                LeadDiscoveryJob::TARGET_NEEDS_REDESIGN => 'Websites that need an update/redesign',
                LeadDiscoveryJob::TARGET_NO_WEBSITE => 'Businesses without a website',
            ],
            'suggestedNiches' => [
                'Accountants',
                'Architects',
                'Auto repair shops',
                'Beauty salons',
                'Car dealers',
                'Car rentals',
                'Chiropractors',
                'Dentists',
                'Doctors',
                'Gyms',
                'Health clinics',
                'Insurance agencies',
                'Interior designers',
                'IT companies',
                'Kindergartens',
                'Language schools',
                'Law firms',
                'Local hotels',
                'Marketing agencies',
                'Massage therapists',
                'Medical spas',
                'Music schools',
                'Opticians',
                'Photographers',
                'Physiotherapists',
                'Private schools',
                'Real estate agencies',
                'Restaurants',
                'Software companies',
                'Spas',
                'Travel agencies',
                'Veterinary clinics',
                'Wedding photographers',
                'Yoga studios',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'niche_mode' => ['required', Rule::in(['specific', 'random'])],
            'niche' => ['nullable', 'string', 'max:120', 'required_if:niche_mode,specific'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'result_limit' => [
                'required',
                'integer',
                'min:'.LeadDiscoveryJob::MIN_RESULT_LIMIT,
                'max:'.LeadDiscoveryJob::MAX_RESULT_LIMIT,
            ],
            'target' => ['required', Rule::in([
                LeadDiscoveryJob::TARGET_NEEDS_REDESIGN,
                LeadDiscoveryJob::TARGET_NO_WEBSITE,
            ])],
        ]);

        LeadDiscoveryJob::create([
            'niche' => $validated['niche_mode'] === 'random' ? null : $validated['niche'],
            'random_niche' => $validated['niche_mode'] === 'random',
            'city' => $validated['city'],
            'country' => $validated['country'] ?? null,
            'result_limit' => $validated['result_limit'],
            'target' => $validated['target'],
            'status' => LeadDiscoveryJob::STATUS_QUEUED,
        ]);

        return redirect()
            ->route('admin.lead-discovery.index')
            ->with('status', 'Lead discovery job queued. The local worker will pick it up and return lead cards.');
    }
}
