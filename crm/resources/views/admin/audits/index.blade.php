@extends('layouts.admin')

@section('title', 'Audit Review')
@section('subtitle', 'Review submitted audit results and approve or reject opportunities.')

@section('content')
    <section class="panel">
        <form method="get" class="form-grid">
            <div>
                <label for="minimum_redesign_score">Minimum redesign score</label>
                <input id="minimum_redesign_score" name="minimum_redesign_score" type="number" min="0" max="100" value="{{ $minimumScore }}">
            </div>
            <div class="actions" style="align-items: end;">
                <button type="submit">Filter</button>
                <a class="button secondary" href="{{ route('admin.audits.index') }}">Clear</a>
            </div>
        </form>
    </section>

    <section class="panel">
        <table>
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Overall</th>
                    <th>Redesign</th>
                    <th>SEO</th>
                    <th>Lead Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($audits as $audit)
                    <tr>
                        <td>
                            <a href="{{ route('admin.audits.show', $audit) }}">{{ $audit->lead->business_name }}</a><br>
                            <span class="muted">{{ $audit->lead->website_url }}</span>
                        </td>
                        <td>{{ $audit->overall_score ?? 'n/a' }}</td>
                        <td><span class="score {{ ($audit->redesign_score ?? 0) >= 75 ? 'high' : 'mid' }}">{{ $audit->redesign_score ?? 'n/a' }}</span></td>
                        <td>{{ $audit->seo_score ?? 'n/a' }}</td>
                        <td><span class="badge">{{ str_replace('_', ' ', $audit->lead->status) }}</span></td>
                        <td>{{ $audit->created_at->format('Y-m-d H:i') }}</td>
                        <td><a class="button secondary" href="{{ route('admin.audits.show', $audit) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No audits found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">{{ $audits->links() }}</div>
    </section>
@endsection

