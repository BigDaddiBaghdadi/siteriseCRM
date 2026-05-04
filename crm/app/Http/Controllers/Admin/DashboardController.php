<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditJob;
use App\Models\Lead;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'leadCount' => Lead::count(),
            'queuedJobs' => AuditJob::where('status', AuditJob::STATUS_QUEUED)->count(),
            'auditingJobs' => AuditJob::where('status', AuditJob::STATUS_AUDITING)->count(),
            'reviewCount' => Lead::where('status', Lead::STATUS_NEEDS_REVIEW)->count(),
            'recentLeads' => Lead::latest()->limit(8)->get(),
            'recentAudits' => Audit::with('lead')->latest()->limit(8)->get(),
        ]);
    }
}

