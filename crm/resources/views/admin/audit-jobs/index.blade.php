@extends('layouts.admin')

@section('title', 'Audit Jobs')
@section('subtitle', 'Queue state for portable workers.')

@section('content')
    <section class="panel">
        <form method="get" class="form-grid">
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Any status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="actions" style="align-items: end;">
                <button type="submit">Filter</button>
                <a class="button secondary" href="{{ route('admin.audit-jobs.index') }}">Clear</a>
            </div>
        </form>
    </section>

    <section class="panel">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Lead</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Attempts</th>
                    <th>Locked</th>
                    <th>Error</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr>
                        <td>#{{ $job->id }}</td>
                        <td>
                            <a href="{{ route('admin.leads.show', $job->lead) }}">{{ $job->lead->business_name }}</a><br>
                            <span class="muted">{{ $job->lead->website_url }}</span>
                        </td>
                        <td><span class="badge">{{ $job->status }}</span></td>
                        <td>{{ $job->priority }}</td>
                        <td>{{ $job->attempts }}</td>
                        <td>
                            {{ $job->locked_by ?: 'n/a' }}
                            @if ($job->locked_at)
                                <br><span class="muted">{{ $job->locked_at->format('Y-m-d H:i') }}</span>
                            @endif
                        </td>
                        <td>{{ $job->last_error ?: 'n/a' }}</td>
                        <td>
                            @if ($job->status === \App\Models\AuditJob::STATUS_FAILED)
                                <form method="post" action="{{ route('admin.audit-jobs.retry', $job) }}">
                                    @csrf
                                    <button type="submit">Retry</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No audit jobs found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">{{ $jobs->links() }}</div>
    </section>
@endsection

