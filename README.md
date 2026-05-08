# Website Audit CRM

Standalone CRM and portable worker for auditing public business websites.

The CRM is the durable system of record. The worker is a movable process that can run on this server during development and later on a local machine.

## Repository Layout

```text
crm/       Laravel CRM application
worker/    Portable Python audit worker
docs/      API, deployment, and scoring notes
PLAN.md    Product and architecture plan
```

## Current Build State

- Neutral architecture plan is written.
- Laravel CRM scaffold is installed.
- Python worker scaffold can perform a first-pass HTML audit and submit results through the planned API.
- Worker has no direct database access and no dependency on any public website.

## Setup Docs

- `docs/server-setup.md` — CRM server/database setup
- `docs/local-development.md` — Windows/local development and portable worker setup
- `docs/worker-deployment.md` — worker deployment notes

## Validation

```bash
cd crm
php artisan test
```

```bash
python3 -m py_compile worker/src/audit_worker/*.py
```

