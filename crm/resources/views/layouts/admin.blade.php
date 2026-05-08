<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Website Audit CRM')</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6f7f9;
            --panel: #ffffff;
            --text: #1f2933;
            --muted: #677383;
            --line: #d9dee7;
            --accent: #0f766e;
            --accent-dark: #115e59;
            --danger: #b42318;
            --warn: #b54708;
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
            background: #172026;
            color: #e5edf1;
            padding: 22px 18px;
        }
        .brand { font-size: 17px; font-weight: 700; margin-bottom: 22px; }
        .nav { display: grid; gap: 6px; }
        .nav form { margin: 14px 0 0; }
        .nav button { width: 100%; background: transparent; border-color: #3a4a54; color: #dbe5ea; justify-content: flex-start; }
        .nav button:hover { background: #26333b; }
        .nav a {
            color: #dbe5ea;
            padding: 9px 10px;
            border-radius: 6px;
        }
        .nav a:hover, .nav a.active { background: #26333b; text-decoration: none; }
        .main { padding: 24px; min-width: 0; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        h1 { font-size: 24px; line-height: 1.2; margin: 0; letter-spacing: 0; }
        h2 { font-size: 16px; margin: 0 0 12px; }
        .muted { color: var(--muted); }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
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
        .button.secondary, button.secondary { background: #fff; color: var(--text); border-color: var(--line); }
        .button.danger, button.danger { background: var(--danger); border-color: var(--danger); }
        .badge {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #edf2f7;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
        }
        .score { font-weight: 700; }
        .score.high { color: var(--danger); }
        .score.mid { color: var(--warn); }
        .flash {
            border: 1px solid #9ad6cb;
            background: #e9fbf7;
            color: #134e4a;
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
        textarea { min-height: 110px; resize: vertical; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .form-row-full { grid-column: 1 / -1; }
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
            background: #101820;
            color: #e5edf1;
            padding: 12px;
            border-radius: 6px;
            white-space: pre-wrap;
        }
        .pagination { margin-top: 14px; }
        .lead-card-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .lead-card { overflow: hidden; background: var(--panel); border: 1px solid var(--line); border-radius: 10px; }
        .lead-card-media { background: #e9edf3; min-height: 170px; display: grid; place-items: center; }
        .lead-card-media img { display: block; width: 100%; height: 220px; object-fit: cover; object-position: top; }
        .screenshot-placeholder { color: var(--muted); font-weight: 700; }
        .lead-card-body { padding: 15px; display: grid; gap: 12px; }
        .lead-card-header { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
        .lead-card h3 { margin: 0 0 3px; font-size: 17px; }
        .score-pill { min-width: 58px; border-radius: 10px; padding: 7px; text-align: center; font-size: 22px; font-weight: 800; background: #fef3c7; color: #92400e; }
        .score-pill.high { background: #fee2e2; color: #991b1b; }
        .score-pill span { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
        .contact-lines { display: flex; flex-wrap: wrap; gap: 8px 12px; color: var(--muted); }
        .insight-block { border-top: 1px solid var(--line); padding-top: 10px; }
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { position: static; }
            .grid-4, .grid-2, .form-grid, .lead-card-grid { grid-template-columns: 1fr; }
            .topbar { align-items: flex-start; flex-direction: column; }
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

