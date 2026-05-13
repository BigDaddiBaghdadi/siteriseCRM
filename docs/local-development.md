# Local Development Setup

This repository is designed so the CRM can run on a server while the worker can run from a separate local machine.

## Recommended local role

A local Windows machine can be used as:

- a development workstation
- a portable audit worker host
- a future browser/screenshot worker host

For production, keep the CRM on a server with HTTPS and a real database. The local worker should communicate with the CRM only through the worker HTTP API and a per-machine worker token.

## Windows prerequisites

Install:

- PHP 8.2+ with common Laravel extensions enabled: `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo_sqlite`, `sqlite3`, `zip`
- Composer 2.x
- Node.js/npm
- Python 3.11+

Example package-manager installs:

```powershell
winget install --id Python.Python.3.11 --exact
winget install --id PHP.PHP.NTS.8.4 --exact
```

Composer can be installed globally or as a local `composer.phar`.

## CRM local setup

From `crm/`:

```powershell
composer install
copy .env.example .env
php artisan key:generate
```

For simple local development, SQLite is enough:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Create the SQLite file and run migrations:

```powershell
New-Item -ItemType File -Force database/database.sqlite
php artisan migrate --force
```

Install and build frontend assets:

```powershell
npm install
npm run build
```

Run tests:

```powershell
php artisan test
```

Run the local CRM:

```powershell
php artisan serve --host=127.0.0.1 --port=8010
```

Or use the repo helper from the repository root:

```powershell
.\scripts\run-local-crm.ps1
```

Use `-Install` the first time if dependencies are missing, and `-Build` when
you want a production asset build before serving locally.

Open:

```text
http://127.0.0.1:8010/admin
```

## Worker local setup

From `worker/`:

```powershell
py -3.11 -m venv .venv
.\.venv\Scripts\python.exe -m pip install --upgrade pip
.\.venv\Scripts\python.exe -m pip install -e .
copy .env.example .env
```

Run a standalone smoke audit:

```powershell
.\.venv\Scripts\python.exe -m audit_worker audit-url https://example.com
```

Run one CRM-backed job after `CRM_API_BASE` and `WORKER_TOKEN` are configured:

```powershell
.\.venv\Scripts\python.exe -m audit_worker run-once
```

## Production direction

Recommended online shape:

1. Host the CRM on Alan's server under a domain/subdomain, for example `https://crm.example.com`.
2. Use MariaDB/MySQL or PostgreSQL on the server for durable CRM data.
3. Create one worker token per machine.
4. Run the worker locally with `CRM_API_BASE=https://crm.example.com` and its own `WORKER_TOKEN`.
5. Keep worker concurrency at `1` until browser/screenshot jobs are stable.
6. Do not put server database credentials on worker machines.

## Validation completed during initial local setup

- PHP dependencies installed successfully.
- CRM migrations ran successfully against SQLite.
- CRM frontend build passed.
- Laravel test suite passed.
- Python worker installed in editable mode under a Python 3.11 virtual environment.
- Worker smoke audit against `https://example.com` returned structured scores/issues/recommendations.
