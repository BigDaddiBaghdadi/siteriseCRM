@extends('layouts.admin')

@section('title', 'Audit #'.$audit->id)
@section('subtitle', $audit->lead->business_name)

@section('actions')
    <form method="post" action="{{ route('admin.audits.approve', $audit) }}">
        @csrf
        <button type="submit">Approve</button>
    </form>
    <form method="post" action="{{ route('admin.audits.reject', $audit) }}">
        @csrf
        <button type="submit" class="danger">Reject</button>
    </form>
@endsection

@section('content')
    <div class="grid grid-4">
        <div class="panel">
            <div class="muted">Overall</div>
            <div class="metric">{{ $audit->overall_score ?? 'n/a' }}</div>
        </div>
        <div class="panel">
            <div class="muted">Redesign</div>
            <div class="metric">{{ $audit->redesign_score ?? 'n/a' }}</div>
        </div>
        <div class="panel">
            <div class="muted">SEO</div>
            <div class="metric">{{ $audit->seo_score ?? 'n/a' }}</div>
        </div>
        <div class="panel">
            <div class="muted">Lead status</div>
            <div class="metric" style="font-size: 18px;">{{ str_replace('_', ' ', $audit->lead->status) }}</div>
        </div>
    </div>

    <section class="panel">
        <h2>Lead</h2>
        <div class="grid grid-2">
            <div><strong>Business</strong><br><a href="{{ route('admin.leads.show', $audit->lead) }}">{{ $audit->lead->business_name }}</a></div>
            <div><strong>Website</strong><br><a href="{{ $audit->lead->website_url }}" target="_blank" rel="noreferrer">{{ $audit->lead->website_url }}</a></div>
            <div><strong>Category</strong><br>{{ $audit->lead->category ?: 'n/a' }}</div>
            <div><strong>Location</strong><br>{{ trim(($audit->lead->city ?: '').' '.($audit->lead->country ?: '')) ?: 'n/a' }}</div>
        </div>
    </section>

    <section class="panel">
        <h2>Summary</h2>
        <p>{{ $audit->business_summary ?: 'No summary submitted.' }}</p>
    </section>

    <div class="grid grid-2">
        <section class="panel">
            <h2>Issues</h2>
            @if ($audit->issues_json)
                <ul class="list">
                    @foreach ($audit->issues_json as $issue)
                        <li>{{ $issue }}</li>
                    @endforeach
                </ul>
            @else
                <div class="muted">No issues submitted.</div>
            @endif
        </section>

        <section class="panel">
            <h2>Recommendations</h2>
            @if ($audit->recommendations_json)
                <ul class="list">
                    @foreach ($audit->recommendations_json as $recommendation)
                        <li>{{ $recommendation }}</li>
                    @endforeach
                </ul>
            @else
                <div class="muted">No recommendations submitted.</div>
            @endif
        </section>
    </div>

    <div class="grid grid-2">
        <section class="panel">
            <h2>Contact</h2>
            <div class="pre">{{ json_encode($audit->contact_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</div>
        </section>

        <section class="panel">
            <h2>Technology</h2>
            <div class="pre">{{ json_encode($audit->technology_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</div>
        </section>
    </div>

    <section class="panel">
        <h2>Website snapshots</h2>
        <div class="grid grid-2 compare-grid">
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
        </div>
    </section>

    <section class="panel">
        <h2>Generated redesign concept</h2>
        @if ($audit->redesign_concept_json)
            <div class="grid grid-2">
                <div>
                    <strong>Style direction</strong>
                    <ul class="list">
                        @foreach (($audit->redesign_concept_json['style_notes'] ?? []) as $note)
                            <li>{{ $note }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <strong>Pitch copy</strong>
                    <div class="pre">{{ $audit->redesign_concept_json['hero_copy'] ?? 'n/a' }}</div>
                </div>
            </div>
        @else
            <div class="muted">No redesign concept submitted yet.</div>
        @endif

        @if ($audit->redesign_mockup_path)
            <div style="margin-top: 14px;">
                <strong>Mock front page</strong><br>
                <a class="button secondary" href="{{ $audit->redesign_mockup_path }}" target="_blank" rel="noreferrer">Open generated mockup</a>
                <iframe class="mockup-frame" src="{{ $audit->redesign_mockup_path }}"></iframe>
            </div>
        @endif
    </section>
@endsection

