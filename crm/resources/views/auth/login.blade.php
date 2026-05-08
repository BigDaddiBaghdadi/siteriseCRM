<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · SiteRise CRM</title>
    <style>
        :root { color-scheme: light; --bg: #f6f7f9; --panel: #fff; --text: #1f2933; --muted: #677383; --line: #d9dee7; --accent: #0f766e; --accent-dark: #115e59; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: var(--bg); color: var(--text); font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .card { width: min(420px, calc(100vw - 32px)); background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 26px; box-shadow: 0 18px 50px rgba(15, 23, 42, .08); }
        h1 { margin: 0 0 6px; font-size: 24px; }
        p { margin: 0 0 22px; color: var(--muted); }
        label { display: block; font-weight: 700; margin: 14px 0 6px; }
        input { width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 10px 11px; font: inherit; }
        .row { display: flex; align-items: center; gap: 8px; margin: 14px 0; color: var(--muted); }
        .row input { width: auto; }
        button { width: 100%; min-height: 40px; border: 0; border-radius: 8px; background: var(--accent); color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        button:hover { background: var(--accent-dark); }
        .errors { border: 1px solid #fecaca; background: #fff1f2; color: #991b1b; padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <main class="card">
        <h1>SiteRise CRM</h1>
        <p>Login to view lead discovery, audits, and pitch opportunities.</p>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <label class="row">
                <input type="checkbox" name="remember" value="1">
                Remember this device
            </label>

            <button type="submit">Login</button>
        </form>
    </main>
</body>
</html>
