@extends('layouts.admin')

@section('title', 'Leads')
@section('subtitle', 'Create, filter, and queue business websites for audit.')

@section('actions')
    <a class="button" href="{{ route('admin.leads.create') }}">New Lead</a>
@endsection

@section('content')
    <section class="panel">
        <form method="get" class="form-grid">
            <div>
                <label for="search">Search</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Business, website, city, category">
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Any status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="actions form-row-full">
                <button type="submit">Filter</button>
                <a class="button secondary" href="{{ route('admin.leads.index') }}">Clear</a>
            </div>
        </form>
    </section>

    <section class="panel">
        <table>
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Website</th>
                    <th>City</th>
                    <th>Status</th>
                    <th>Jobs</th>
                    <th>Audits</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leads as $lead)
                    <tr>
                        <td><a href="{{ route('admin.leads.show', $lead) }}">{{ $lead->business_name }}</a><br><span class="muted">{{ $lead->category }}</span></td>
                        <td><a href="{{ $lead->website_url }}" target="_blank" rel="noreferrer">{{ parse_url($lead->website_url, PHP_URL_HOST) }}</a></td>
                        <td>{{ $lead->city ?: 'n/a' }}</td>
                        <td><span class="badge">{{ str_replace('_', ' ', $lead->status) }}</span></td>
                        <td>{{ $lead->audit_jobs_count }}</td>
                        <td>{{ $lead->audits_count }}</td>
                        <td class="actions">
                            <a class="button secondary" href="{{ route('admin.leads.edit', $lead) }}">Edit</a>
                            <form method="post" action="{{ route('admin.leads.queue-audit', $lead) }}">
                                @csrf
                                <button type="submit">Queue</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No leads found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">{{ $leads->links() }}</div>
    </section>
@endsection

