<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Website Audit CRM')</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f8f7ff;
            --panel: #ffffff;
            --text: #090909;
            --muted: #6f6a7d;
            --line: #ededed;
            --accent: #602eff;
            --accent-dark: #4d21cf;
            --accent-soft: rgb(96 46 255 / 13%);
            --warm: #ee9982;
            --warm-soft: #fff0eb;
            --navy: #050737;
            --danger: #e52d27;
            --warn: #ee9982;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
            line-height: 1.45;
        }
        a { color: var(--accent-dark); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .shell { min-height: 100vh; display: grid; grid-template-columns: 240px 1fr; }
        .sidebar {
            background:
                radial-gradient(circle at top left, rgb(96 46 255 / 34%), transparent 34%),
                var(--navy);
            color: #e5edf1;
            padding: 22px 18px;
        }
        .brand { font-size: 17px; font-weight: 700; margin-bottom: 22px; }
        .nav { display: grid; gap: 6px; }
        .nav form { margin: 14px 0 0; }
        .nav button { width: 100%; background: transparent; border-color: rgb(255 255 255 / 20%); color: #fff; justify-content: flex-start; }
        .nav button:hover { background: rgb(255 255 255 / 10%); }
        .nav a {
            color: rgb(255 255 255 / 82%);
            padding: 9px 10px;
            border-radius: 6px;
        }
        .nav a:hover, .nav a.active { background: rgb(96 46 255 / 44%); color: #fff; text-decoration: none; }
        .main { padding: 24px; min-width: 0; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        h1 { font-size: 24px; line-height: 1.2; margin: 0; letter-spacing: 0; }
        h2 { font-size: 16px; margin: 0 0 12px; }
        .muted { color: var(--muted); }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: 0 16px 36px rgb(5 7 55 / 5%);
            padding: 16px;
            margin-bottom: 16px;
        }
        .grid { display: grid; gap: 16px; }
        .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .metric { font-size: 26px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 9px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; }
        tr:last-child td { border-bottom: 0; }
        .actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .button, button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 7px 11px;
            border: 1px solid var(--accent);
            border-radius: 6px;
            background: var(--accent);
            color: #fff;
            font: inherit;
            cursor: pointer;
        }
        .button:hover, button:hover { background: var(--accent-dark); text-decoration: none; }
        .button.secondary, button.secondary { background: #fff; color: var(--accent); border-color: rgb(96 46 255 / 22%); }
        .button.secondary:hover, button.secondary:hover { background: var(--accent-soft); color: var(--accent-dark); }
        .button.danger, button.danger { background: var(--danger); border-color: var(--danger); }
        .badge {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent-dark);
            font-size: 12px;
            font-weight: 700;
        }
        .score { font-weight: 700; }
        .score.high { color: var(--danger); }
        .score.mid { color: var(--warn); }
        .flash {
            border: 1px solid rgb(96 46 255 / 22%);
            background: var(--accent-soft);
            color: var(--accent-dark);
            padding: 10px 12px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        label { display: block; font-weight: 700; margin-bottom: 5px; }
        input, select, textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 9px 10px;
            font: inherit;
            background: #fff;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
            outline: 0;
        }
        textarea { min-height: 110px; resize: vertical; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .discovery-form { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .form-row-full { grid-column: 1 / -1; }
        .inline-field { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 10px; align-items: stretch; }
        .choice-field { border: 0; padding: 0; margin: 0; }
        .choice-field legend { font-weight: 700; margin-bottom: 8px; }
        .choice-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .choice-card {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 48px;
            padding: 11px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
        }
        .choice-card:has(input:checked) { border-color: var(--accent); background: var(--accent-soft); }
        .choice-card input { width: auto; }
        .discovery-progress {
            display: none;
            border: 1px solid rgb(96 46 255 / 20%);
            background: linear-gradient(135deg, var(--accent-soft), var(--warm-soft));
            border-radius: 8px;
            padding: 12px;
        }
        .discovery-progress.is-active { display: grid; gap: 9px; }
        .progress-header { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .progress-track {
            position: relative;
            overflow: hidden;
            height: 9px;
            border-radius: 999px;
            background: rgb(96 46 255 / 14%);
        }
        .progress-track span {
            position: absolute;
            inset: 0 auto 0 0;
            width: 42%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--accent), var(--warm));
            animation: discovery-progress 1.35s ease-in-out infinite;
        }
        @keyframes discovery-progress {
            0% { transform: translateX(-105%); }
            55% { transform: translateX(85%); }
            100% { transform: translateX(245%); }
        }
        .errors {
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #991b1b;
            padding: 10px 12px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        .list { margin: 0; padding-left: 18px; }
        .pre {
            overflow: auto;
            background: var(--navy);
            color: #fff;
            padding: 12px;
            border-radius: 6px;
            white-space: pre-wrap;
        }
        .pagination { margin-top: 14px; }
        .lead-card-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .lead-card-list { display: grid; grid-template-columns: minmax(0, 1fr); gap: 14px; }
        .lead-card { overflow: hidden; background: var(--panel); border: 1px solid var(--line); border-radius: 10px; }
        .lead-card-media { background: #f1efff; min-height: 170px; display: grid; place-items: center; }
        .lead-card-media img { display: block; width: 100%; height: 220px; object-fit: cover; object-position: top; }
        .screenshot-placeholder { color: var(--muted); font-weight: 700; }
        .lead-list-card {
            display: grid;
            grid-template-columns: minmax(260px, 34%) minmax(0, 1fr);
            overflow: hidden;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease, background-color .14s ease;
        }
        .lead-list-card:hover,
        .lead-list-card:focus-within {
            transform: translateY(-2px);
            border-color: rgb(96 46 255 / 32%);
            box-shadow: 0 22px 52px rgb(5 7 55 / 12%);
            background: linear-gradient(180deg, #fff, #fff 72%, #fbfaff);
        }
        .lead-list-card.clickable-card { cursor: pointer; }
        .lead-list-card.clickable-card a,
        .lead-list-card.clickable-card button { cursor: pointer; }
        .lead-list-card.is-deleting {
            pointer-events: none;
            overflow: hidden;
            animation: lead-card-delete .42s ease forwards;
        }
        .lead-list-card.is-deleting .lead-list-media,
        .lead-list-card.is-deleting .lead-list-body {
            animation: lead-card-content-delete .34s ease forwards;
        }
        @keyframes lead-card-delete {
            0% {
                opacity: 1;
                transform: translateY(0) scale(1);
                height: var(--delete-height, auto);
                margin-bottom: 0;
            }
            55% {
                opacity: 0;
                transform: translateY(-8px) scale(.985);
                border-color: rgb(238 153 130 / 60%);
                box-shadow: 0 18px 42px rgb(238 153 130 / 16%);
            }
            100% {
                opacity: 0;
                transform: translateY(-12px) scale(.97);
                height: 0;
                margin-bottom: -14px;
                border-width: 0;
            }
        }
        @keyframes lead-card-content-delete {
            to {
                opacity: 0;
                filter: blur(3px);
            }
        }
        .lead-list-media {
            background: #f1efff;
            min-height: 260px;
            display: grid;
            place-items: center;
            border-right: 1px solid var(--line);
            padding: 10px;
        }
        .lead-list-media img {
            display: block;
            width: 100%;
            height: 100%;
            max-height: 360px;
            object-fit: contain;
            object-position: center top;
            border: 1px solid rgb(96 46 255 / 12%);
            border-radius: 6px;
            background: #fff;
        }
        .lead-list-body { padding: 16px; display: grid; gap: 12px; align-content: start; }
        .lead-card-body { padding: 15px; display: grid; gap: 12px; }
        .lead-card-header { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
        .lead-card h3 { margin: 0 0 3px; font-size: 17px; }
        .lead-list-card h3 { margin: 0 0 3px; font-size: 18px; }
        .score-pill {
            min-width: 58px;
            border: 1px solid rgb(238 153 130 / 34%);
            border-radius: 10px;
            padding: 7px;
            text-align: center;
            font-size: 22px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--warm-soft), #fff);
            color: var(--navy);
            box-shadow: inset 0 1px 0 rgb(255 255 255 / 70%);
        }
        .score-pill.high {
            border-color: rgb(96 46 255 / 28%);
            background: linear-gradient(135deg, var(--accent), var(--navy));
            color: #fff;
        }
        .score-pill span { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
        .score-pill.has-tooltip { position: relative; outline: 0; }
        .score-pill.has-tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            z-index: 10;
            right: 0;
            top: calc(100% + 10px);
            width: min(280px, 72vw);
            padding: 10px 12px;
            border: 1px solid rgb(96 46 255 / 24%);
            border-radius: 8px;
            background: var(--navy);
            color: #fff;
            box-shadow: 0 18px 42px rgb(5 7 55 / 20%);
            font-size: 12px;
            line-height: 1.35;
            font-weight: 650;
            text-align: left;
            text-transform: none;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-4px);
            transition: opacity .14s ease, transform .14s ease;
        }
        .score-pill.has-tooltip:hover::after,
        .score-pill.has-tooltip:focus::after {
            opacity: 1;
            transform: translateY(0);
        }
        .contact-lines { display: flex; flex-wrap: wrap; gap: 8px 12px; color: var(--muted); }
        .email-line { flex-basis: 100%; }
        .email-line a { font-weight: 700; overflow-wrap: anywhere; }
        .insight-block { border-top: 1px solid var(--line); padding-top: 10px; }
        .insight-list { margin-top: 8px; }
        .insight-list li { margin-bottom: 5px; }
        .snapshot { display: block; width: 100%; max-height: 520px; object-fit: cover; object-position: top; border: 1px solid var(--line); border-radius: 8px; margin-top: 8px; background: #f1efff; }
        .snapshot.mobile { max-width: 260px; }
        .mockup-frame { width: 100%; min-height: 520px; border: 1px solid var(--line); border-radius: 8px; margin-top: 10px; background: #fff; }
        .mockup-frame.compact { min-height: 420px; }
        .lead-review-list { display: grid; gap: 18px; }
        .lead-review-row { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; box-shadow: 0 18px 42px rgb(5 7 55 / 7%); }
        .lead-review-main { display: grid; grid-template-columns: 330px 1fr; gap: 0; }
        .lead-review-shot { background: #f1efff; min-height: 240px; display: grid; place-items: center; border-right: 1px solid var(--line); }
        .lead-review-shot img { width: 100%; height: 100%; min-height: 240px; object-fit: cover; object-position: top; display: block; }
        .lead-review-summary { padding: 16px; display: grid; gap: 12px; }
        .score-stack { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
        .score-strip { display: flex; flex-wrap: wrap; gap: 10px; }
        .score-strip span { background: #fbfaff; border: 1px solid var(--line); border-radius: 999px; padding: 5px 9px; color: var(--muted); }
        .score-strip strong { color: var(--text); }
        .lead-review-details { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1px; background: var(--line); border-top: 1px solid var(--line); }
        .detail-panel { background: #fff; padding: 14px; min-height: 120px; }
        .mini-json { overflow: auto; max-height: 120px; margin-top: 8px; padding: 10px; border-radius: 6px; background: #fbfaff; color: #343434; white-space: pre-wrap; font-size: 12px; }
        .inline-review-more { border-top: 1px solid var(--line); padding: 0; }
        .inline-review-more summary { cursor: pointer; padding: 13px 16px; font-weight: 800; color: var(--accent-dark); background: #fbfaff; }
        .inline-review-grid { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 16px; padding: 16px; }
        .inline-review-wide { grid-column: 1 / -1; }
        .concept-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 8px; }
        .clickable-card { cursor: pointer; transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease; }
        .clickable-card:hover { transform: translateY(-2px); box-shadow: 0 18px 45px rgb(5 7 55 / 11%); border-color: rgb(96 46 255 / 28%); }
        .card-actions { border-top: 1px solid var(--line); padding-top: 10px; }
        .review-hero { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(280px, .6fr); gap: 24px; align-items: start; }
        .review-pitch { font-size: 28px; line-height: 1.12; font-weight: 850; letter-spacing: -.04em; margin: 0 0 12px; }
        .review-contact-card { border: 1px solid var(--line); border-radius: 10px; padding: 14px; background: #fbfaff; }
        .contact-lines.stacked { display: grid; gap: 8px; }
        .pitch-email-subject {
            display: grid;
            gap: 5px;
            margin-bottom: 10px;
            padding: 11px 12px;
            border: 1px solid rgb(96 46 255 / 18%);
            border-radius: 8px;
            background: #fbfaff;
        }
        .pitch-email-subject strong { color: var(--navy); font-size: 15px; }
        .pitch-email-field {
            position: relative;
            border: 1px solid rgb(96 46 255 / 18%);
            border-radius: 8px;
            background: #fff;
            transition: border-color .16s ease, box-shadow .16s ease;
        }
        .pitch-email-field:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }
        .pitch-email-field.is-copy-success {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgb(15 118 110 / 14%);
        }
        .pitch-email-box {
            min-height: 430px;
            resize: vertical;
            white-space: pre-wrap;
            line-height: 1.55;
            color: var(--navy);
            background: transparent;
            border: 0;
            border-radius: 8px;
            padding-right: 92px;
        }
        .pitch-email-box:focus {
            box-shadow: none;
        }
        .copy-email-button {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 32px;
            padding: 6px 9px;
            border: 0;
            border-radius: 7px;
            background: rgb(255 255 255 / 88%);
            color: var(--muted);
            font-weight: 700;
            cursor: pointer;
            transition: background-color .12s ease, color .12s ease;
        }
        .copy-email-button:hover,
        .copy-email-button:focus-visible {
            background: var(--accent-soft);
            color: var(--navy);
            text-decoration: none;
            outline: 0;
        }
        .copy-email-button.is-copied {
            background: #ecfdf5;
            color: #0f766e;
        }
        .copy-email-icon {
            display: inline-flex;
            width: 16px;
            height: 16px;
        }
        .copy-email-icon svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .copy-email-icon-check { display: none; }
        .copy-email-button.is-copied .copy-email-icon-copy { display: none; }
        .copy-email-button.is-copied .copy-email-icon-check { display: inline-flex; }
        .review-snapshot-grid { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 18px; align-items: start; }
        .review-snapshot { max-height: 760px; }
        .review-mobile { max-height: 760px; }
        .section-heading-row { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 10px; }
        .reason-panel.bad { border-color: rgb(229 45 39 / 24%); background: #fff7f7; }
        .reason-panel.good { border-color: rgb(96 46 255 / 22%); background: #fbfaff; }
        .review-list li { margin-bottom: 8px; }
        .readable-stack { display: grid; gap: 10px; }
        .readable-row {
            display: grid;
            grid-template-columns: 130px minmax(0, 1fr);
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid var(--line);
        }
        .readable-row:last-child { border-bottom: 0; }
        .readable-label { color: var(--muted); font-weight: 800; font-size: 12px; text-transform: uppercase; }
        .readable-pill {
            display: inline-flex;
            align-items: center;
            max-width: 100%;
            margin: 0 6px 6px 0;
            padding: 5px 9px;
            border-radius: 999px;
            border: 1px solid rgb(96 46 255 / 18%);
            background: #fbfaff;
            color: var(--accent-dark);
            font-weight: 700;
            overflow-wrap: anywhere;
        }
        .readable-pill.strong { color: var(--text); background: #fff; }
        .readable-pill.positive { border-color: rgb(15 118 110 / 22%); background: #ecfdf5; color: #0f766e; }
        .readable-pill.warning { border-color: rgb(238 153 130 / 36%); background: var(--warm-soft); color: #8b321e; }
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { position: static; }
            .grid-4, .grid-2, .form-grid, .discovery-form, .choice-grid, .inline-field, .lead-card-grid, .lead-list-card, .lead-review-main, .lead-review-details, .inline-review-grid, .concept-grid, .review-hero, .review-snapshot-grid { grid-template-columns: 1fr; }
            .lead-list-media { border-right: 0; border-bottom: 1px solid var(--line); }
            .lead-review-shot { border-right: 0; border-bottom: 1px solid var(--line); }
            .inline-review-wide { grid-column: auto; }
            .topbar { align-items: flex-start; flex-direction: column; }
            .readable-row { grid-template-columns: 1fr; gap: 5px; }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">Website Audit CRM</div>
        <nav class="nav">
            <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>Dashboard</a>
            <a href="{{ route('admin.lead-discovery.index') }}" @class(['active' => request()->routeIs('admin.lead-discovery.*')])>Lead Discovery</a>
            <a href="{{ route('admin.leads.index') }}" @class(['active' => request()->routeIs('admin.leads.*')])>Leads</a>
            <a href="{{ route('admin.audit-jobs.index') }}" @class(['active' => request()->routeIs('admin.audit-jobs.*')])>Audit Jobs</a>
            <a href="{{ route('admin.audits.index') }}" @class(['active' => request()->routeIs('admin.audits.*')])>Audit Review</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="secondary">Logout</button>
            </form>
        </nav>
    </aside>
    <main class="main">
        <div class="topbar">
            <div>
                <h1>@yield('title')</h1>
                @hasSection('subtitle')
                    <div class="muted">@yield('subtitle')</div>
                @endif
            </div>
            <div class="actions">@yield('actions')</div>
        </div>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">
                <strong>Fix these fields:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
