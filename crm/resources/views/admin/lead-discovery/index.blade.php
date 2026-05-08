@extends('layouts.admin')

@section('title', 'Lead Discovery')
@section('subtitle', 'Find real local business websites, review audits, compare snapshots, and manage leads in one place.')

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
                        <option value="{{ $limit }}" @selected((int) old('result_limit', 5) === $limit)>{{ $limit }} leads</option>
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
                <span class="muted">The local worker returns real website leads with snapshots, audit notes, and redesign mockups.</span>
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
                <h2 style="margin: 0;">Lead review rows</h2>
                <div class="muted">Everything needed to review a lead is now inline: contact, audit, snapshots, redesign mockup, approve/reject, and delete.</div>
            </div>
        </div>

        <div class="lead-review-list">
            @forelse ($leads as $lead)
                @php($audit = $lead->latestAudit)
                <article class="lead-review-row">
                    <div class="lead-review-main">
                        <div class="lead-review-shot">
                            @if ($audit?->desktop_screenshot_path)
                                <img src="{{ $audit->desktop_screenshot_path }}" alt="Screenshot for {{ $lead->business_name }}">
                            @else
                                <div class="screenshot-placeholder">Screenshot pending</div>
                            @endif
                        </div>

                        <div class="lead-review-summary">
                            <div class="lead-card-header">
                                <div>
                                    <h3>{{ $lead->business_name }}</h3>
                                    <div class="muted">{{ $lead->category ?: 'Unknown niche' }} · {{ $lead->city ?: 'Unknown city' }}{{ $lead->country ? ', '.$lead->country : '' }}</div>
                                </div>
                                <div class="score-stack">
                                    @if ($audit?->redesign_score !== null)
                                        <div class="score-pill {{ $audit->redesign_score >= 75 ? 'high' : 'mid' }}">
                                            {{ $audit->redesign_score }}
                                            <span>pitch</span>
                                        </div>
                                    @endif
                                    <span class="badge">{{ str_replace('_', ' ', $lead->status) }}</span>
                                </div>
                            </div>

                            <div class="contact-lines">
                                @if ($lead->website_url)
                                    <a href="{{ $lead->website_url }}" target="_blank" rel="noreferrer">{{ parse_url($lead->website_url, PHP_URL_HOST) ?: $lead->website_url }}</a>
                                @endif
                                @if ($lead->phone)<span>{{ $lead->phone }}</span>@endif
                                @if ($lead->email)<span>{{ $lead->email }}</span>@endif
                                @if ($lead->source_url)<a href="{{ $lead->source_url }}" target="_blank" rel="noreferrer">Source</a>@endif
                            </div>

                            @if ($lead->notes)
                                <div class="muted">{{ $lead->notes }}</div>
                            @endif

                            @if ($audit)
                                <div class="score-strip">
                                    <span><strong>{{ $audit->overall_score ?? 'n/a' }}</strong> overall</span>
                                    <span><strong>{{ $audit->seo_score ?? 'n/a' }}</strong> SEO</span>
                                    <span><strong>{{ $audit->mobile_score ?? 'n/a' }}</strong> mobile</span>
                                    <span><strong>{{ $audit->performance_score ?? 'n/a' }}</strong> speed</span>
                                </div>
                            @endif

                            <div class="actions">
                                @if ($audit)
                                    <form method="post" action="{{ route('admin.audits.approve', $audit) }}">
                                        @csrf
                                        <button type="submit">Approve</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.audits.reject', $audit) }}">
                                        @csrf
                                        <button type="submit" class="secondary">Reject</button>
                                    </form>
                                    @if ($audit->redesign_mockup_path)
                                        <a class="button secondary" href="{{ $audit->redesign_mockup_path }}" target="_blank" rel="noreferrer">Open mockup</a>
                                    @endif
                                @else
                                    <form method="post" action="{{ route('admin.leads.queue-audit', $lead) }}">
                                        @csrf
                                        <button type="submit">Queue audit</button>
                                    </form>
                                @endif
                                <form method="post" action="{{ route('admin.leads.destroy', $lead) }}" onsubmit="return confirm('Delete this lead and its audits?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    @if ($audit)
                        <div class="lead-review-details">
                            <div class="detail-panel">
                                <strong>Pitch insights</strong>
                                @if ($audit->issues_json)
                                    <ul class="list">
                                        @foreach (array_slice($audit->issues_json ?? [], 0, 5) as $issue)
                                            <li>{{ $issue }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="muted">No issues submitted.</div>
                                @endif
                            </div>

                            <div class="detail-panel">
                                <strong>What to change</strong>
                                @if ($audit->recommendations_json)
                                    <ul class="list">
                                        @foreach (array_slice($audit->recommendations_json ?? [], 0, 5) as $recommendation)
                                            <li>{{ $recommendation }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="muted">No recommendations submitted.</div>
                                @endif
                            </div>

                            <div class="detail-panel">
                                <strong>Contact found</strong>
                                <div class="mini-json">{{ json_encode($audit->contact_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</div>
                            </div>

                            <div class="detail-panel">
                                <strong>Technology</strong>
                                <div class="mini-json">{{ json_encode($audit->technology_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</div>
                            </div>
                        </div>

                        <details class="inline-review-more">
                            <summary>Snapshots and generated redesign comparison</summary>
                            <div class="inline-review-grid">
                                <div>
                                    <strong>Current desktop</strong>
                                    @if ($audit->desktop_screenshot_path)
                                        <img class="snapshot" src="{{ $audit->desktop_screenshot_path }}" alt="Current desktop screenshot">
                                    @else
                                        <div class="muted">Not submitted yet</div>
                                    @endif
                                </div>
                                <div>
                                    <strong>Current mobile</strong>
                                    @if ($audit->mobile_screenshot_path)
                                        <img class="snapshot mobile" src="{{ $audit->mobile_screenshot_path }}" alt="Current mobile screenshot">
                                    @else
                                        <div class="muted">Not submitted yet</div>
                                    @endif
                                </div>
                                <div class="inline-review-wide">
                                    <strong>Generated redesign concept</strong>
                                    @if ($audit->redesign_concept_json)
                                        <div class="concept-grid">
                                            <div>
                                                <div class="muted">Pitch copy</div>
                                                <div class="pre">{{ $audit->redesign_concept_json['hero_copy'] ?? 'n/a' }}</div>
                                            </div>
                                            <div>
                                                <div class="muted">Style direction</div>
                                                <ul class="list">
                                                    @foreach (($audit->redesign_concept_json['style_notes'] ?? []) as $note)
                                                        <li>{{ $note }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @else
                                        <div class="muted">No redesign concept submitted yet.</div>
                                    @endif

                                    @if ($audit->redesign_mockup_path)
                                        <iframe class="mockup-frame compact" src="{{ $audit->redesign_mockup_path }}"></iframe>
                                    @endif
                                </div>
                            </div>
                        </details>
                    @else
                        <div class="lead-review-details">
                            <div class="detail-panel muted">Audit pending. Once the worker analyzes this lead, review details will show inline here.</div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="panel muted">No leads yet. Queue a discovery job to start finding opportunities.</div>
            @endforelse
        </div>
    </section>
@endsection
