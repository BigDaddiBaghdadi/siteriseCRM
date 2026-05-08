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
        return view('admin.lead-discovery.index', [
            'jobs' => LeadDiscoveryJob::latest()->limit(12)->get(),
            'leads' => Lead::with('latestAudit')
                ->latest()
                ->limit(24)
                ->get(),
            'limits' => LeadDiscoveryJob::RESULT_LIMITS,
            'targets' => [
                LeadDiscoveryJob::TARGET_NEEDS_REDESIGN => 'Find real websites that need redesign/rebuild',
            ],
            'suggestedNiches' => [
                'Dentists',
                'Restaurants',
                'Gyms',
                'Beauty salons',
                'Real estate agencies',
                'Auto repair shops',
                'Law firms',
                'Accountants',
                'Veterinary clinics',
                'Local hotels',
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
            'result_limit' => ['required', 'integer', Rule::in(LeadDiscoveryJob::RESULT_LIMITS)],
            'target' => ['required', Rule::in([
                LeadDiscoveryJob::TARGET_NEEDS_REDESIGN,
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
