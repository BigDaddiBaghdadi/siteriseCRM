# Portable Website Audit Worker

This worker is designed to run on this server during development and later move to a local machine without changing the CRM.

It has one integration point: the CRM HTTP API.

## Responsibilities

- Poll the CRM for audit jobs.
- Visit public business websites.
- Extract basic page and contact signals.
- Calculate first-pass audit scores.
- Submit structured results back to the CRM.

Browser screenshots and deeper rendering checks will be added behind the same worker interface, so the CRM does not need to know where the worker runs.

## Setup

```bash
python3 -m venv .venv
. .venv/bin/activate
pip install -e .
cp .env.example .env
```

Edit `.env` with the CRM URL and worker token.

## Run One Job

```bash
python -m audit_worker run-once
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

