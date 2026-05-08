@extends('layouts.admin')

@section('title', $lead->business_name)
@section('subtitle', $lead->website_url)

@section('actions')
    <a class="button secondary" href="{{ route('admin.leads.edit', $lead) }}">Edit</a>
    <form method="post" action="{{ route('admin.leads.queue-audit', $lead) }}">
        @csrf
        <button type="submit">Queue Audit</button>
    </form>
    <form method="post" action="{{ route('admin.leads.destroy', $lead) }}" onsubmit="return confirm('Delete this lead and its audits?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="danger">Delete Lead</button>
    </form>
@endsection

@section('content')
    <section class="panel">
        <h2>Lead Details</h2>
        <div class="grid grid-2">
            <div>
                <strong>Status</strong><br>
                <span class="badge">{{ str_replace('_', ' ', $lead->status) }}</span>
            </div>
            <div>
                <strong>Website</strong><br>
                <a href="{{ $lead->website_url }}" target="_blank" rel="noreferrer">{{ $lead->website_url }}</a>
            </div>
            <div><strong>Category</strong><br>{{ $lead->category ?: 'n/a' }}</div>
            <div><strong>Location</strong><br>{{ trim(($lead->city ?: '').' '.($lead->country ?: '')) ?: 'n/a' }}</div>
            <div><strong>Email</strong><br>{{ $lead->email ?: 'n/a' }}</div>
            <div><strong>Phone</strong><br>{{ $lead->phone ?: 'n/a' }}</div>
            <div><strong>Source</strong><br>{{ $lead->source ?: 'n/a' }}</div>
            <div><strong>Source URL</strong><br>
                @if ($lead->source_url)
                    <a href="{{ $lead->source_url }}" target="_blank" rel="noreferrer">{{ $lead->source_url }}</a>
                @else
                    n/a
                @endif
            </div>
        </div>
        @if ($lead->notes)
            <div style="margin-top: 14px;">
                <strong>Notes</strong>
                <div class="pre">{{ $lead->notes }}</div>
            </div>
        @endif
    </section>

    <section class="panel">
        <h2>Audit Jobs</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Attempts</th>
                    <th>Locked By</th>
                    <th>Last Error</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lead->auditJobs as $job)
                    <tr>
                        <td>#{{ $job->id }}</td>
                        <td><span class="badge">{{ $job->status }}</span></td>
                        <td>{{ $job->attempts }}</td>
                        <td>{{ $job->locked_by ?: 'n/a' }}</td>
                        <td>{{ $job->last_error ?: 'n/a' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No audit jobs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="panel">
        <h2>Audits</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Overall</th>
                    <th>Redesign</th>
                    <th>SEO</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lead->audits as $audit)
                    <tr>
                        <td>#{{ $audit->id }}</td>
                        <td>{{ $audit->overall_score ?? 'n/a' }}</td>
                        <td><span class="score {{ ($audit->redesign_score ?? 0) >= 75 ? 'high' : 'mid' }}">{{ $audit->redesign_score ?? 'n/a' }}</span></td>
                        <td>{{ $audit->seo_score ?? 'n/a' }}</td>
                        <td>{{ $audit->created_at->format('Y-m-d H:i') }}</td>
                        <td><a class="button secondary" href="{{ route('admin.audits.show', $audit) }}">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No audits submitted yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection

