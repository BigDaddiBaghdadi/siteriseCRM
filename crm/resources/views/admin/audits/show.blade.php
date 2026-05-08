@extends('layouts.admin')

@section('title', $audit->lead->business_name)
@section('subtitle', 'Complete website review and redesign pitch')

@section('actions')
    <a class="button secondary" href="{{ route('admin.lead-discovery.index') }}">Back to Lead Discovery</a>
    <a class="button secondary" href="{{ $audit->lead->website_url }}" target="_blank" rel="noreferrer">Open website</a>
    <form method="post" action="{{ route('admin.audits.approve', $audit) }}">
        @csrf
        <button type="submit">Approve</button>
    </form>
    <form method="post" action="{{ route('admin.audits.reject', $audit) }}">
        @csrf
        <button type="submit" class="secondary">Reject</button>
    </form>
    <form method="post" action="{{ route('admin.leads.destroy', $audit->lead) }}" onsubmit="return confirm('Delete this lead and its audits?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="danger">Delete</button>
    </form>
@endsection

@section('content')
    @php($concept = $audit->redesign_concept_json ?? [])

    <div class="grid grid-4">
        <div class="panel"><div class="muted">Overall</div><div class="metric">{{ $audit->overall_score ?? 'n/a' }}</div></div>
        <div class="panel"><div class="muted">Redesign pitch</div><div class="metric">{{ $audit->redesign_score ?? 'n/a' }}</div></div>
        <div class="panel"><div class="muted">SEO</div><div class="metric">{{ $audit->seo_score ?? 'n/a' }}</div></div>
        <div class="panel"><div class="muted">Lead status</div><div class="metric" style="font-size: 18px;">{{ str_replace('_', ' ', $audit->lead->status) }}</div></div>
    </div>

    <section class="panel review-hero">
        <div>
            <h2>Pitch idea</h2>
            <p class="review-pitch">{{ $concept['hero_copy'] ?? 'A clearer, faster website that turns local visitors into calls and bookings.' }}</p>
            <p class="muted">{{ $concept['subcopy'] ?? ($audit->business_summary ?: 'No summary submitted.') }}</p>
        </div>
        <div class="review-contact-card">
            <h2>Contact information</h2>
            <div class="contact-lines stacked">
                <a href="{{ $audit->lead->website_url }}" target="_blank" rel="noreferrer">{{ $audit->lead->website_url }}</a>
                @if ($audit->lead->phone)<span>{{ $audit->lead->phone }}</span>@endif
                @if ($audit->lead->email)<span>{{ $audit->lead->email }}</span>@endif
                <span>{{ $audit->lead->category ?: 'Unknown niche' }} · {{ $audit->lead->city ?: 'Unknown city' }}{{ $audit->lead->country ? ', '.$audit->lead->country : '' }}</span>
                @if ($audit->lead->source_url)<a href="{{ $audit->lead->source_url }}" target="_blank" rel="noreferrer">Discovery source</a>@endif
            </div>
        </div>
    </section>

    <section class="panel">
        <h2>Website snapshot</h2>
        <div class="review-snapshot-grid">
            <div>
                <strong>Desktop</strong>
                @if ($audit->desktop_screenshot_path)
                    <img class="snapshot review-snapshot" src="{{ $audit->desktop_screenshot_path }}" alt="Current desktop screenshot">
                @else
                    <div class="muted">Desktop snapshot missing.</div>
                @endif
            </div>
            <div>
                <strong>Mobile</strong>
                @if ($audit->mobile_screenshot_path)
                    <img class="snapshot mobile review-mobile" src="{{ $audit->mobile_screenshot_path }}" alt="Current mobile screenshot">
                @else
                    <div class="muted">Mobile snapshot missing.</div>
                @endif
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="section-heading-row">
            <h2>Proposed design</h2>
            @if ($audit->redesign_mockup_path)
                <a class="button secondary" href="{{ $audit->redesign_mockup_path }}" target="_blank" rel="noreferrer">Open full mockup</a>
            @endif
        </div>
        @if ($audit->redesign_mockup_path)
            <iframe class="mockup-frame" src="{{ $audit->redesign_mockup_path }}"></iframe>
        @else
            <div class="muted">No proposed design mockup submitted yet.</div>
        @endif
    </section>

    <div class="grid grid-2">
        <section class="panel reason-panel bad">
            <h2>Why the website sucks</h2>
            @if ($audit->issues_json)
                <ul class="list review-list">
                    @foreach ($audit->issues_json as $issue)
                        <li>{{ $issue }}</li>
                    @endforeach
                </ul>
            @else
                <div class="muted">No issues submitted.</div>
            @endif
        </section>

        <section class="panel reason-panel good">
            <h2>What can be done better</h2>
            @if ($audit->recommendations_json)
                <ul class="list review-list">
                    @foreach ($audit->recommendations_json as $recommendation)
                        <li>{{ $recommendation }}</li>
                    @endforeach
                </ul>
            @else
                <div class="muted">No recommendations submitted.</div>
            @endif
        </section>
    </div>

    <section class="panel">
        <h2>Style direction</h2>
        @if ($concept)
            <div class="concept-grid">
                <div>
                    <strong>Proposition language / copy</strong>
                    <div class="pre">{{ $concept['hero_copy'] ?? 'n/a' }}</div>
                </div>
                <div>
                    <strong>Design notes</strong>
                    <ul class="list">
                        @foreach (($concept['style_notes'] ?? []) as $note)
                            <li>{{ $note }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @else
            <div class="muted">No redesign concept submitted yet.</div>
        @endif
    </section>

    <div class="grid grid-2">
        <section class="panel">
            <h2>Contact found by worker</h2>
            <div class="pre">{{ json_encode($audit->contact_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</div>
        </section>

        <section class="panel">
            <h2>Technology</h2>
            <div class="pre">{{ json_encode($audit->technology_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</div>
        </section>
    </div>
@endsection
