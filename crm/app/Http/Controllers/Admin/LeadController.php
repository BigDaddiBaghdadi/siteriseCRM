<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditJob;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $query = Lead::query()->withCount(['auditJobs', 'audits'])->latest();

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($query) use ($search): void {
                $query->where('business_name', 'like', "%{$search}%")
                    ->orWhere('website_url', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.leads.index', [
            'leads' => $query->paginate(25)->withQueryString(),
            'statuses' => $this->statuses(),
            'selectedStatus' => $status,
            'search' => $search ?? '',
        ]);
    }

    public function create(): View
    {
        return view('admin.leads.create', [
            'lead' => new Lead(['status' => Lead::STATUS_NEW]),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $lead = Lead::create($this->validatedLead($request));

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with('status', 'Lead created.');
    }

    public function show(Lead $lead): View
    {
        $lead->load([
            'auditJobs' => fn ($query) => $query->latest(),
            'audits' => fn ($query) => $query->latest(),
        ]);

        return view('admin.leads.show', [
            'lead' => $lead,
        ]);
    }

    public function edit(Lead $lead): View
    {
        return view('admin.leads.edit', [
            'lead' => $lead,
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $lead->update($this->validatedLead($request));

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with('status', 'Lead updated.');
    }

    public function destroy(Request $request, Lead $lead): RedirectResponse|JsonResponse
    {
        $lead->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'deleted',
                'lead_id' => (string) $lead->id,
            ]);
        }

        return redirect()
            ->route('admin.leads.index')
            ->with('status', 'Lead deleted.');
    }

    public function queueAudit(Lead $lead): RedirectResponse
    {
        $existingOpenJob = $lead->auditJobs()
            ->whereIn('status', [AuditJob::STATUS_QUEUED, AuditJob::STATUS_AUDITING])
            ->exists();

        if ($existingOpenJob) {
            return back()->with('status', 'This lead already has an open audit job.');
        }

        $lead->auditJobs()->create([
            'status' => AuditJob::STATUS_QUEUED,
            'priority' => 100,
        ]);

        $lead->update([
            'status' => Lead::STATUS_QUEUED_FOR_AUDIT,
        ]);

        return back()->with('status', 'Audit job queued.');
    }

    /**
     * @return array<string, string>
     */
    private function statuses(): array
    {
        return [
            Lead::STATUS_NEW => 'New',
            Lead::STATUS_QUEUED_FOR_AUDIT => 'Queued for audit',
            Lead::STATUS_AUDITING => 'Auditing',
            Lead::STATUS_AUDIT_FAILED => 'Audit failed',
            Lead::STATUS_AUDITED => 'Audited',
            Lead::STATUS_NEEDS_REVIEW => 'Needs review',
            Lead::STATUS_APPROVED => 'Approved',
            Lead::STATUS_REJECTED => 'Rejected',
            Lead::STATUS_EXPORTED => 'Exported',
            Lead::STATUS_ARCHIVED => 'Archived',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedLead(Request $request): array
    {
        return $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'website_url' => ['required', 'url', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
