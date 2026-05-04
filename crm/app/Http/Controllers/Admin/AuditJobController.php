<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditJob;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditJobController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditJob::query()->with('lead')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.audit-jobs.index', [
            'jobs' => $query->paginate(25)->withQueryString(),
            'statuses' => [
                AuditJob::STATUS_QUEUED => 'Queued',
                AuditJob::STATUS_AUDITING => 'Auditing',
                AuditJob::STATUS_AUDITED => 'Audited',
                AuditJob::STATUS_FAILED => 'Failed',
            ],
            'selectedStatus' => $status,
        ]);
    }

    public function retry(AuditJob $auditJob): RedirectResponse
    {
        $auditJob->update([
            'status' => AuditJob::STATUS_QUEUED,
            'locked_by' => null,
            'locked_at' => null,
            'last_error' => null,
        ]);

        $auditJob->lead()->update([
            'status' => Lead::STATUS_QUEUED_FOR_AUDIT,
        ]);

        return back()->with('status', 'Audit job queued for retry.');
    }
}

