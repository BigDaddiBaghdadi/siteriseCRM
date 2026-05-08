@extends('layouts.admin')

@section('title', 'Lead Discovery')
@section('subtitle', 'Find local businesses without websites or with websites that need a stronger pitch-ready redesign.')

@section('content')
    <section class="panel">
        <h2>Start a discovery job</h2>
        <form method="POST" action="{{ route('admin.lead-discovery.store') }}" class="form-grid">
            @csrf

            <div>
                <label for="niche_mode">Niche</label>
                <select id="niche_mode" name="niche_mode">
                    <option value="specific" @selected(old('niche_mode', 'specific') === 'specific')>Choose a niche</option>
                    <option value="random" @selected(old('niche_mode') === 'random')>Random niche</option>
                </select>
            </div>

            <div>
                <label for="niche">Business niche</label>
                <input id="niche" name="niche" list="suggested-niches" value="{{ old('niche') }}" placeholder="Dentists, gyms, salons...">
                <datalist id="suggested-niches">
                    @foreach ($suggestedNiches as $niche)
                        <option value="{{ $niche }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div>
                <label for="city">City</label>
                <input id="city" name="city" value="{{ old('city') }}" placeholder="Sofia" required>
            </div>

            <div>
                <label for="country">Country</label>
                <input id="country" name="country" value="{{ old('country', 'Bulgaria') }}" placeholder="Bulgaria">
            </div>

            <div>
                <label for="result_limit">Results wanted</label>
                <select id="result_limit" name="result_limit">
                    @foreach ($limits as $limit)
                        <option value="{{ $limit }}" @selected((int) old('result_limit', 15) === $limit)>{{ $limit }} leads</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="target">Opportunity type</label>
                <select id="target" name="target">
                    @foreach ($targets as $value => $label)
                        <option value="{{ $value }}" @selected(old('target', 'needs_redesign') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-row-full actions">
                <button type="submit">Queue discovery</button>
                <span class="muted">The local worker will scrape/analyze and return pitch-ready lead cards.</span>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Recent discovery jobs</h2>
        <table>
            <thead>
                <tr>
                    <th>Job</th>
                    <th>City</th>
                    <th>Target</th>
                    <th>Status</th>
                    <th>Found</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr>
                        <td>{{ $job->nicheLabel() }}</td>
                        <td>{{ $job->city }}{{ $job->country ? ', '.$job->country : '' }}</td>
                        <td>{{ $job->targetLabel() }}</td>
                        <td><span class="badge">{{ str_replace('_', ' ', $job->status) }}</span></td>
                        <td>{{ $job->leads_found }} / {{ $job->result_limit }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No discovery jobs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section>
        <div class="topbar" style="margin-bottom: 10px;">
            <div>
                <h2 style="margin: 0;">Lead cards</h2>
                <div class="muted">Pitch-oriented cards from discovered/imported leads and their latest audit.</div>
            </div>
        </div>

        <div class="lead-card-grid">
            @forelse ($leads as $lead)
                @php($audit = $lead->latestAudit)
                <article class="lead-card">
                    <div class="lead-card-media">
                        @if ($audit?->desktop_screenshot_path)
                            <img src="{{ $audit->desktop_screenshot_path }}" alt="Screenshot for {{ $lead->business_name }}">
                        @else
                            <div class="screenshot-placeholder">
                                {{ $lead->website_url ? 'Screenshot pending' : 'No website found' }}
                            </div>
                        @endif
                    </div>

                    <div class="lead-card-body">
                        <div class="lead-card-header">
                            <div>
                                <h3>{{ $lead->business_name }}</h3>
                                <div class="muted">{{ $lead->category ?: 'Unknown niche' }} · {{ $lead->city ?: 'Unknown city' }}</div>
                            </div>
                            @if ($audit?->redesign_score !== null)
                                <div class="score-pill {{ $audit->redesign_score >= 75 ? 'high' : 'mid' }}">
                                    {{ $audit->redesign_score }}
                                    <span>pitch</span>
                                </div>
                            @endif
                        </div>

                        <div class="contact-lines">
                            @if ($lead->website_url)
                                <a href="{{ $lead->website_url }}" target="_blank" rel="noreferrer">{{ parse_url($lead->website_url, PHP_URL_HOST) ?: $lead->website_url }}</a>
                            @else
                                <span class="badge">No website detected</span>
                            @endif
                            @if ($lead->phone)<span>{{ $lead->phone }}</span>@endif
                            @if ($lead->email)<span>{{ $lead->email }}</span>@endif
                        </div>

                        @if ($audit)
                            <div class="insight-block">
                                <strong>Pitch insights</strong>
                                <ul class="list">
                                    @foreach (array_slice($audit->issues_json ?? [], 0, 3) as $issue)
                                        <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="insight-block">
                                <strong>What to change</strong>
                                <ul class="list">
                                    @foreach (array_slice($audit->recommendations_json ?? [], 0, 3) as $recommendation)
                                        <li>{{ $recommendation }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="muted">Audit pending. Once the worker analyzes this lead, pitch insights will show here.</p>
                        @endif

                        <div class="actions">
                            <a class="button secondary" href="{{ route('admin.leads.show', $lead) }}">Open lead</a>
                            @if ($audit)
                                <a class="button secondary" href="{{ route('admin.audits.show', $audit) }}">Review audit</a>
                                @if ($audit->redesign_mockup_path)
                                    <a class="button secondary" href="{{ $audit->redesign_mockup_path }}" target="_blank" rel="noreferrer">Mockup</a>
                                @endif
                            @endif
                            <form method="post" action="{{ route('admin.leads.destroy', $lead) }}" onsubmit="return confirm('Delete this lead and its audits?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="panel muted">No leads yet. Queue a discovery job to start finding opportunities.</div>
            @endforelse
        </div>
    </section>
@endsection
