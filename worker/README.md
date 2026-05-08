# Portable Website Audit Worker

This worker is designed to run on this server during development and later move to a local machine without changing the CRM.

It has one integration point: the CRM HTTP API.

## Responsibilities

- Poll the CRM for audit jobs.
- Visit public business websites.
- Extract basic page and contact signals.
- Calculate first-pass audit scores.
- Poll the CRM for lead discovery jobs.
- Submit pitch-ready lead discovery results back to the CRM.

Browser screenshots and deeper rendering checks will be added behind the same worker interface, so the CRM does not need to know where the worker runs.

## Setup

```bash
python3 -m venv .venv
. .venv/bin/activate
pip install -e .
cp .env.example .env
```

Edit `.env` with the CRM URL and worker token.

## Run One Audit Job

```bash
python -m audit_worker run-once
```

## Run One Lead Discovery Job

```bash
python -m audit_worker run-discovery-once
```

## Run Continuously

```bash
python -m audit_worker work
```

The continuous worker checks Lead Discovery first, then regular Audit jobs. If no work is available, it sleeps for `POLL_INTERVAL_SECONDS`.

For a smoke test that exits quickly:

```bash
python -m audit_worker work --max-cycles 1
```

Discovery providers:

- `DISCOVERY_PROVIDER=demo` generates deterministic demo lead cards for end-to-end CRM testing.
- `DISCOVERY_PROVIDER=osm` uses OpenStreetMap/Nominatim/Overpass with no API key. It works best for mapped categories such as Dentists, Beauty Salons, Gyms, Restaurants, Plumbers, Law Firms, and Accountants.

A paid provider adapter can be added later for broader coverage, such as Google Places, SerpAPI, DataForSEO, or Apify.

## Windows Local Worker

From the repo root:

```powershell
.\scripts\run-local-worker.ps1 -MaxCycles 1
.\scripts\run-local-worker.ps1
```

To install it as a logon scheduled task:

```powershell
.\scripts\install-local-worker-task.ps1
```

## Local URL Audit Smoke Test

```bash
python -m audit_worker audit-url https://example.com
```

## Transfer Rule

The worker must remain portable:

- no direct CRM database access
- no hardcoded server paths
- no dependency on public marketing websites
- all configuration through environment variables

