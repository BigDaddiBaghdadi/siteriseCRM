<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditReviewController extends Controller
{
    public function index(Request $request): View
    {
        $query = Audit::query()->with('lead')->latest();

        if ($minimumScore = $request->integer('minimum_redesign_score')) {
            $query->where('redesign_score', '>=', $minimumScore);
        }

        return view('admin.audits.index', [
            'audits' => $query->paginate(25)->withQueryString(),
            'minimumScore' => $minimumScore ?: null,
        ]);
    }

    public function show(Audit $audit): View
    {
        $audit->load('lead', 'job');

        return view('admin.audits.show', [
            'audit' => $audit,
        ]);
    }

    public function approve(Audit $audit): RedirectResponse
    {
        $audit->lead()->update([
            'status' => Lead::STATUS_APPROVED,
        ]);

        return back()->with('status', 'Lead approved.');
    }

    public function reject(Audit $audit): RedirectResponse
    {
        $audit->lead()->update([
            'status' => Lead::STATUS_REJECTED,
        ]);

        return back()->with('status', 'Lead rejected.');
    }
}

