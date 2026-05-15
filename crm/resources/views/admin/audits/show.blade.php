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
    @php
        $concept = $audit->redesign_concept_json ?? [];
        $contact = $audit->contact_json ?? [];
        $technology = $audit->technology_json ?? [];
        $emails = collect([$audit->lead->email])->merge($contact['emails'] ?? [])->filter()->unique()->values();
        $phones = collect([$audit->lead->phone])->merge($contact['phones'] ?? [])->filter()->unique()->values();
        $contactPage = $contact['contact_page'] ?? null;
        $issues = collect($audit->issues_json ?? [])->filter();
        $recommendations = collect($audit->recommendations_json ?? [])->filter();

        if ($issues->count() < 6) {
            $issues = $issues
                ->merge($audit->redesign_score !== null && $audit->redesign_score >= 70 ? ['High redesign score means the current site likely has a visible opportunity for a stronger first impression.'] : [])
                ->merge($emails->isEmpty() ? ['No public email was found by the worker, which makes outreach and lead capture weaker.'] : [])
                ->merge($phones->isEmpty() ? ['No phone number was easy for the worker to detect, which hurts mobile conversion.'] : [])
                ->merge(empty($technology['analytics'] ?? false) ? ['No obvious analytics tracking was detected, so the business may not know what produces leads.'] : [])
                ->merge(empty($technology['cms'] ?? null) ? ['The website platform was not obvious, which can make maintenance and redesign planning harder to qualify quickly.'] : [])
                ->unique()
                ->values();
        }

        if ($recommendations->count() < 6) {
            $recommendations = $recommendations
                ->merge(['Make the hero section clearer with one strong offer and one primary call button.'])
                ->merge(['Add visible trust proof: reviews, certifications, client logos, before/after work, or local credibility signals.'])
                ->merge(['Create a cleaner service section that is easy to scan on mobile.'])
                ->merge(['Add conversion tracking for calls, forms, and booking clicks.'])
                ->merge(['Use stronger spacing, headings, and contrast so the page feels more modern immediately.'])
                ->unique()
                ->values();
        }

        $businessName = $audit->lead->business_name;
        $websiteUrl = $audit->lead->website_url;
        $websiteHost = parse_url($websiteUrl, PHP_URL_HOST) ?: $websiteUrl;
        $categoryPhrase = $audit->lead->category ? mb_strtolower($audit->lead->category) : 'услуги';
        $cityPhrase = $audit->lead->city ? ' в '.$audit->lead->city : '';
        $demoLinkPlaceholder = '[ТУК ДОБАВИ ЛИНК КЪМ САЙТА/ДЕМОТО, КОЕТО СМЕ НАПРАВИЛИ ЗА ТЯХ]';

        $pitchPoints = collect();
        if ($audit->redesign_score !== null && $audit->redesign_score >= 70) {
            $pitchPoints->push('първото впечатление може да бъде много по-силно и по-модерно');
        }
        if ($issues->contains(fn ($issue) => str_contains(mb_strtolower((string) $issue), 'call to action'))) {
            $pitchPoints->push('основният бутон/следваща стъпка не е достатъчно ясно видим');
        }
        if ($phones->isEmpty()) {
            $pitchPoints->push('телефонът не се намира достатъчно лесно, особено за хора от мобилен телефон');
        }
        if ($emails->isEmpty()) {
            $pitchPoints->push('не се вижда ясен имейл за директно запитване');
        }
        if (empty($contactPage)) {
            $pitchPoints->push('пътят до контакт може да бъде по-кратък и очевиден');
        }
        if (empty($technology['analytics'] ?? false)) {
            $pitchPoints->push('не се вижда проследяване на запитванията, което затруднява измерването на резултатите');
        }
        if ($pitchPoints->count() < 3) {
            $pitchPoints = $pitchPoints
                ->merge([
                    'услугите и причините човек да избере бизнеса могат да се покажат по-ясно',
                    'сайтът може да води по-бързо към обаждане, запитване или резервация',
                    'доверителните елементи като отзиви, примери и конкретни предимства могат да бъдат по-видими',
                ])
                ->unique()
                ->values();
        }
        $pitchPoints = $pitchPoints->take(4)->values();

        $pitchSubject = 'Идея за по-силен сайт за '.$businessName;
        $pitchEmailLines = [
            'Тема: '.$pitchSubject,
            '',
            'Здравейте,',
            '',
            'Попаднах на сайта на '.$businessName.' - '.$websiteUrl,
            '',
            'Разгледах го като потенциален клиент, който търси '.$categoryPhrase.$cityPhrase.'. Има добра основа, но според мен сайтът може да носи повече запитвания, ако първото впечатление и пътят до контакт станат по-ясни.',
            '',
            'Няколко конкретни неща, които забелязах:',
        ];

        foreach ($pitchPoints as $point) {
            $pitchEmailLines[] = '- '.$point;
        }

        $pitchEmailLines = array_merge($pitchEmailLines, [
            '',
            'Подготвили сме примерна посока как сайтът може да изглежда по-модерно и по-убедително:',
            $demoLinkPlaceholder,
            '',
            'Идеята е страницата по-бързо да показва какво предлагате, защо човек да ви се довери и как да се свърже с вас без излишно търсене.',
            '',
            'Ако ви е интересно, мога да ви изпратя 2-3 конкретни предложения за подобрения по текущия сайт и как бихме ги направили.',
            '',
            'Поздрави,',
            '[Твоето име]',
        ]);
        $pitchEmail = implode("\n", $pitchEmailLines);
    @endphp

    <div class="grid grid-4">
        <div class="panel"><div class="muted">Overall</div><div class="metric">{{ $audit->overall_score ?? 'n/a' }}</div></div>
        <div class="panel"><div class="muted">Redesign pitch</div><div class="metric">{{ $audit->redesign_score ?? 'n/a' }}</div></div>
        <div class="panel"><div class="muted">SEO</div><div class="metric">{{ $audit->seo_score ?? 'n/a' }}</div></div>
        <div class="panel"><div class="muted">Lead status</div><div class="metric" style="font-size: 18px;">{{ str_replace('_', ' ', $audit->lead->status) }}</div></div>
    </div>

    <section class="panel review-hero">
        <div>
            <div class="section-heading-row">
                <h2>Prepared Bulgarian email</h2>
            </div>
            <div class="pitch-email-subject">
                <span class="readable-label">Subject</span>
                <strong>{{ $pitchSubject }}</strong>
            </div>
            <div class="pitch-email-field">
                <button type="button" class="copy-email-button" data-copy-target="pitch-email" aria-label="Copy email">
                    <span class="copy-email-icon copy-email-icon-copy" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <rect x="9" y="9" width="10" height="10" rx="2"></rect>
                            <path d="M5 15V7a2 2 0 0 1 2-2h8"></path>
                        </svg>
                    </span>
                    <span class="copy-email-icon copy-email-icon-check" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="m5 12 4 4L19 6"></path>
                        </svg>
                    </span>
                    <span class="copy-email-label">Copy</span>
                </button>
                <textarea id="pitch-email" class="pitch-email-box" readonly>{{ $pitchEmail }}</textarea>
            </div>
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
            @if ($issues->isNotEmpty())
                <ul class="list review-list">
                    @foreach ($issues as $issue)
                        <li>{{ $issue }}</li>
                    @endforeach
                </ul>
            @else
                <div class="muted">No issues submitted.</div>
            @endif
        </section>

        <section class="panel reason-panel good">
            <h2>What can be done better</h2>
            @if ($recommendations->isNotEmpty())
                <ul class="list review-list">
                    @foreach ($recommendations as $recommendation)
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
            <div class="readable-stack">
                <div class="readable-row">
                    <span class="readable-label">Emails</span>
                    <div>
                        @forelse ($emails as $email)
                            <a class="readable-pill" href="mailto:{{ $email }}">{{ $email }}</a>
                        @empty
                            <span class="muted">No email found</span>
                        @endforelse
                    </div>
                </div>
                <div class="readable-row">
                    <span class="readable-label">Phones</span>
                    <div>
                        @forelse ($phones as $phone)
                            <a class="readable-pill" href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a>
                        @empty
                            <span class="muted">No phone found</span>
                        @endforelse
                    </div>
                </div>
                <div class="readable-row">
                    <span class="readable-label">Contact page</span>
                    <div>
                        @if ($contactPage)
                            <a href="{{ $contactPage }}" target="_blank" rel="noreferrer">{{ parse_url($contactPage, PHP_URL_HOST) ?: $contactPage }}</a>
                        @else
                            <span class="muted">No dedicated contact page detected</span>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2>Technology</h2>
            <div class="readable-stack">
                <div class="readable-row">
                    <span class="readable-label">Website platform</span>
                    <div>
                        @if (! empty($technology['cms']))
                            <span class="readable-pill strong">{{ $technology['cms'] }}</span>
                        @else
                            <span class="muted">Unknown or custom-built</span>
                        @endif
                    </div>
                </div>
                <div class="readable-row">
                    <span class="readable-label">Analytics</span>
                    <div>
                        <span class="readable-pill {{ ! empty($technology['analytics']) ? 'positive' : 'warning' }}">
                            {{ ! empty($technology['analytics']) ? 'Tracking detected' : 'No tracking detected' }}
                        </span>
                    </div>
                </div>
                <div class="readable-row">
                    <span class="readable-label">Chat / quick inquiry</span>
                    <div>
                        <span class="readable-pill {{ ! empty($technology['chat_widget']) ? 'positive' : 'warning' }}">
                            {{ ! empty($technology['chat_widget']) ? 'Widget detected' : 'No widget detected' }}
                        </span>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        document.querySelectorAll('.copy-email-button').forEach((button) => {
            button.addEventListener('click', async () => {
                const target = document.getElementById(button.dataset.copyTarget);
                if (! target) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(target.value);
                } catch (error) {
                    target.focus();
                    target.select();
                    document.execCommand('copy');
                }

                const label = button.querySelector('.copy-email-label');
                const field = target.closest('.pitch-email-field');
                button.classList.add('is-copied');
                field?.classList.add('is-copy-success');
                if (label) {
                    label.textContent = 'Copied';
                }
                window.setTimeout(() => {
                    button.classList.remove('is-copied');
                    if (label) {
                        label.textContent = 'Copy';
                    }
                }, 1400);
                window.setTimeout(() => {
                    field?.classList.remove('is-copy-success');
                }, 520);
            });
        });
    </script>
@endsection
