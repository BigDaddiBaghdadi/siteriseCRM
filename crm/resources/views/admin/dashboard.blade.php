@extends('layouts.admin')

@section('title', 'Dashboard')
@section('subtitle', 'Operational snapshot for leads, audit jobs, and review volume.')

@section('actions')
    <a class="button" href="{{ route('admin.leads.create') }}">New Lead</a>
@endsection

@section('content')
    <div class="grid grid-4">
        <div class="panel">
            <div class="muted">Total leads</div>
            <div class="metric">{{ $leadCount }}</div>
        </div>
        <div class="panel">
            <div class="muted">Queued jobs</div>
            <div class="metric">{{ $queuedJobs }}</div>
        </div>
        <div class="panel">
            <div class="muted">Auditing now</div>
            <div class="metric">{{ $auditingJobs }}</div>
        </div>
        <div class="panel">
            <div class="muted">Needs review</div>
            <div class="metric">{{ $reviewCount }}</div>
        </div>
    </div>

    <div class="grid grid-2">
        <section class="panel">
            <h2>Recent Leads</h2>
            <table>
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Status</th>
                        <th>Website</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentLeads as $lead)
                        <tr>
                            <td><a href="{{ route('admin.leads.show', $lead) }}">{{ $lead->business_name }}</a></td>
                            <td><span class="badge">{{ str_replace('_', ' ', $lead->status) }}</span></td>
                            <td><a href="{{ $lead->website_url }}" target="_blank" rel="noreferrer">{{ parse_url($lead->website_url, PHP_URL_HOST) }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted">No leads yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="panel">
            <h2>Recent Audits</h2>
            <table>
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Overall</th>
                        <th>Redesign</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentAudits as $audit)
                        <tr>
                            <td><a href="{{ route('admin.audits.show', $audit) }}">{{ $audit->lead->business_name }}</a></td>
                            <td>{{ $audit->overall_score ?? 'n/a' }}</td>
                            <td><span class="score {{ ($audit->redesign_score ?? 0) >= 75 ? 'high' : 'mid' }}">{{ $audit->redesign_score ?? 'n/a' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted">No audits yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection

