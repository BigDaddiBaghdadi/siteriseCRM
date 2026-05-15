@extends('layouts.admin')

@section('title', 'Lead Discovery')
@section('subtitle', 'Queue focused discovery jobs and review returned leads one by one.')

@section('content')
    @php($activeDiscoveryJob = $jobs->first(fn ($job) => in_array($job->status, [\App\Models\LeadDiscoveryJob::STATUS_QUEUED, \App\Models\LeadDiscoveryJob::STATUS_RUNNING], true)))

    <section class="panel">
        <h2>Start a discovery job</h2>
        <form method="POST" action="{{ route('admin.lead-discovery.store') }}" class="discovery-form" id="discovery-form">
            @csrf
            <input type="hidden" id="niche_mode" name="niche_mode" value="specific">

            <div class="form-row-full">
                <label for="niche">Niche</label>
                <div class="inline-field">
                    <input id="niche" name="niche" list="suggested-niches" value="{{ old('niche') }}" placeholder="Dentists, gyms, salons...">
                    <button type="button" class="secondary" id="random-niche-button">Random niche</button>
                </div>
                <div class="muted" id="niche-helper">Choose a niche or use the button to fill this field with a random one.</div>
                <datalist id="suggested-niches">
                    @foreach ($suggestedNiches as $niche)
                        <option value="{{ $niche }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div>
                <label for="city">City</label>
                <input id="city" name="city" list="suggested-cities" value="{{ old('city', 'Sofia') }}" placeholder="Sofia" required>
                <datalist id="suggested-cities">
                    <option value="Sofia"></option>
                    <option value="Plovdiv"></option>
                    <option value="Varna"></option>
                    <option value="Burgas"></option>
                    <option value="London"></option>
                    <option value="Berlin"></option>
                </datalist>
            </div>

            <div>
                <label for="country">Country</label>
                <input id="country" name="country" list="suggested-countries" value="{{ old('country', 'Bulgaria') }}" placeholder="Bulgaria">
                <datalist id="suggested-countries">
                    <option value="Bulgaria"></option>
                    <option value="United Kingdom"></option>
                    <option value="Germany"></option>
                    <option value="United States"></option>
                    <option value="Spain"></option>
                    <option value="Italy"></option>
                </datalist>
            </div>

            <div>
                <label for="result_limit">Amount of leads</label>
                <select id="result_limit" name="result_limit">
                    @foreach ($limits as $limit)
                        <option value="{{ $limit }}" @selected((int) old('result_limit', \App\Models\LeadDiscoveryJob::DEFAULT_RESULT_LIMIT) === $limit)>{{ $limit }} leads</option>
                    @endforeach
                </select>
            </div>

            <fieldset class="choice-field form-row-full">
                <legend>Lead type</legend>
                <div class="choice-grid">
                    @foreach ($targets as $value => $label)
                        <label class="choice-card">
                            <input type="radio" name="target" value="{{ $value }}" @checked(old('target', \App\Models\LeadDiscoveryJob::TARGET_NEEDS_REDESIGN) === $value)>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="form-row-full actions">
                <button type="submit" id="queue-discovery-button">Queue discovery</button>
                <span class="muted">The worker will return lead cards with contact details, screenshots when available, and review notes.</span>
            </div>

            <div class="form-row-full discovery-progress {{ $activeDiscoveryJob ? 'is-active' : '' }}" id="discovery-progress" aria-live="polite">
                <div class="progress-header">
                    <strong id="discovery-progress-title">
                        {{ $activeDiscoveryJob ? 'Discovery job '.str_replace('_', ' ', $activeDiscoveryJob->status) : 'Queueing discovery job' }}
                    </strong>
                    <span class="muted" id="discovery-progress-copy">
                        @if ($activeDiscoveryJob)
                            {{ $activeDiscoveryJob->nicheLabel() }} in {{ $activeDiscoveryJob->city }}{{ $activeDiscoveryJob->country ? ', '.$activeDiscoveryJob->country : '' }}
                        @else
                            Preparing the request for the worker.
                        @endif
                    </span>
                </div>
                <div class="progress-track" role="progressbar" aria-label="Discovery progress">
                    <span></span>
                </div>
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
                <div class="muted">Newest leads appear in a single review column.</div>
            </div>
        </div>

        <div class="lead-card-list">
            @if ($leads->isEmpty())
                <div class="panel muted">No leads yet. Queue a discovery job to start finding opportunities.</div>
            @endif

            @foreach ($leads as $lead)
                @php($audit = $lead->latestAudit)
                @php($reviewUrl = $audit ? route('admin.audits.show', $audit) : route('admin.leads.show', $lead))
                @php($issues = $lead->discovery_issues)
                @php($recommendations = $lead->discovery_recommendations)
                @php($contact = $lead->discovery_contact)
                @php($emails = $lead->discovery_emails)

                <article class="lead-list-card clickable-card" data-review-url="{{ $reviewUrl }}" role="link" tabindex="0" aria-label="Open {{ $lead->business_name }} for further review">
                    <div class="lead-list-media">
                        @if ($audit?->desktop_screenshot_path)
                            <img src="{{ $audit->desktop_screenshot_path }}" alt="Website screenshot for {{ $lead->business_name }}">
                        @else
                            <div class="screenshot-placeholder">
                                {{ $lead->website_url ? 'Screenshot pending' : 'No website found' }}
                            </div>
                        @endif
                    </div>

                    <div class="lead-list-body">
                        <div class="lead-card-header">
                            <div>
                                <h3>{{ $lead->business_name }}</h3>
                                <div class="muted">{{ $lead->category ?: 'Unknown niche' }} - {{ $lead->city ?: 'Unknown city' }}{{ $lead->country ? ', '.$lead->country : '' }}</div>
                            </div>
                            <div class="score-stack">
                                @if ($audit?->redesign_score !== null)
                                    <div
                                        class="score-pill {{ $audit->redesign_score >= 75 ? 'high' : 'mid' }} has-tooltip"
                                        tabindex="0"
                                        data-tooltip="Pitch score estimates how strong this lead is for a redesign offer. Higher scores mean the site has clearer signs of missed conversion, weak trust, or outdated presentation."
                                    >
                                        {{ $audit->redesign_score }}
                                        <span>pitch</span>
                                    </div>
                                @endif
                                <span class="badge">{{ str_replace('_', ' ', $lead->status) }}</span>
                            </div>
                        </div>

                        <div class="contact-lines stacked-on-small">
                            @if ($lead->website_url)
                                <span><strong>Website:</strong> {{ parse_url($lead->website_url, PHP_URL_HOST) ?: $lead->website_url }}</span>
                            @else
                                <span><strong>Website:</strong> none found</span>
                            @endif
                            @if ($lead->phone || ! empty($contact['phones'][0]))
                                <span><strong>Phone:</strong> {{ $lead->phone ?: $contact['phones'][0] }}</span>
                            @endif
                            <span class="email-line">
                                <strong>Emails:</strong>
                                @if ($emails->isNotEmpty())
                                    @foreach ($emails as $email)
                                        <a href="mailto:{{ $email }}">{{ $email }}</a>@if (! $loop->last), @endif
                                    @endforeach
                                @else
                                    none found
                                @endif
                            </span>
                            @if ($lead->source_url)
                                <span><strong>Source:</strong> {{ parse_url($lead->source_url, PHP_URL_HOST) ?: $lead->source_url }}</span>
                            @endif
                        </div>

                        <div class="insight-block">
                            <strong>Why this website is a good pitch</strong>
                            @if ($issues->isNotEmpty())
                                <ul class="list insight-list">
                                    @foreach ($issues as $issue)
                                        <li>{{ is_array($issue) ? json_encode($issue) : $issue }}</li>
                                    @endforeach
                                </ul>
                            @elseif ($lead->notes)
                                <ul class="list insight-list">
                                    <li>{{ $lead->notes }}</li>
                                </ul>
                            @else
                                <div class="muted">No audit notes yet.</div>
                            @endif
                        </div>

                        @if ($recommendations->isNotEmpty())
                            <div class="insight-block">
                                <strong>What can be improved</strong>
                                <ul class="list insight-list">
                                    @foreach ($recommendations as $recommendation)
                                        <li>{{ is_array($recommendation) ? json_encode($recommendation) : $recommendation }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="actions card-actions">
                            <a class="button secondary" href="{{ $reviewUrl }}">Further review</a>
                            <form method="post" action="{{ route('admin.leads.destroy', $lead) }}" onsubmit="return confirm('Delete this lead and its audits?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <script>
        const nicheInput = document.getElementById('niche');
        const helper = document.getElementById('niche-helper');
        const randomButton = document.getElementById('random-niche-button');
        const discoveryForm = document.getElementById('discovery-form');
        const queueButton = document.getElementById('queue-discovery-button');
        const progress = document.getElementById('discovery-progress');
        const progressTitle = document.getElementById('discovery-progress-title');
        const progressCopy = document.getElementById('discovery-progress-copy');
        const suggestedNiches = @json($suggestedNiches);

        function pickRandomNiche() {
            const current = nicheInput.value.trim();
            const choices = suggestedNiches.filter((niche) => niche !== current);
            const pool = choices.length ? choices : suggestedNiches;
            const next = pool[Math.floor(Math.random() * pool.length)];

            nicheInput.value = next;
            nicheInput.focus();
            helper.textContent = `Random niche selected: ${next}`;
        }

        randomButton.addEventListener('click', pickRandomNiche);

        discoveryForm.addEventListener('submit', () => {
            progress.classList.add('is-active');
            progressTitle.textContent = 'Discovery job queued';
            progressCopy.textContent = 'Waiting for the worker to pick it up.';
            queueButton.disabled = true;
            queueButton.textContent = 'Queueing...';
        });

        document.querySelectorAll('[data-review-url]').forEach((card) => {
            card.addEventListener('click', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                if (target?.closest('a, button, form, input, select, textarea, [data-no-card-click]')) {
                    return;
                }

                window.location.href = card.dataset.reviewUrl;
            });

            card.addEventListener('keydown', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                if (
                    event.key === 'Enter'
                    && ! target?.closest('a, button, form, input, select, textarea, [data-no-card-click]')
                ) {
                    window.location.href = card.dataset.reviewUrl;
                }
            });
        });
    </script>
@endsection
