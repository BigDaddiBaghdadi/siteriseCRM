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
                LeadDiscoveryJob::TARGET_NEEDS_REDESIGN => 'Websites that need an update/redesign',
                LeadDiscoveryJob::TARGET_NO_WEBSITE => 'Businesses without a website',
            ],
            'suggestedNiches' => [
                'Accountants',
                'Architects',
                'Auto parts shops',
                'Auto repair shops',
                'Bakeries',
                'Barbers',
                'Bars',
                'Beauty salons',
                'Bike shops',
                'Bookstores',
                'Butchers',
                'Cafes',
                'Car dealers',
                'Car rentals',
                'Car washes',
                'Carpenters',
                'Caterers',
                'Chiropractors',
                'Cleaning services',
                'Clothing stores',
                'Computer repair shops',
                'Dentists',
                'Doctors',
                'Dry cleaners',
                'Electricians',
                'Florists',
                'Furniture stores',
                'Garden centers',
                'Gyms',
                'Hair salons',
                'Handymen',
                'Hardware stores',
                'Home builders',
                'Insurance agencies',
                'Interior designers',
                'Jewelry stores',
                'Kindergartens',
                'Landscapers',
                'Language schools',
                'Law firms',
                'Local hotels',
                'Massage therapists',
                'Mechanics',
                'Moving companies',
                'Music schools',
                'Opticians',
                'Painters',
                'Pest control',
                'Pet groomers',
                'Pharmacies',
                'Photographers',
                'Physiotherapists',
                'Plumbers',
                'Printing shops',
                'Real estate agencies',
                'Restaurants',
                'Roofers',
                'Shoe repair shops',
                'Spas',
                'Tailors',
                'Tattoo studios',
                'Travel agencies',
                'Veterinary clinics',
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
