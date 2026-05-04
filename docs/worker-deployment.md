# Worker Deployment

The worker should be developed on this server first, then moved to a local machine when browser automation is added.

## Development on This Server

```bash
cd /root/website-audit-crm/worker
python3 -m venv .venv
. .venv/bin/activate
pip install -e .
cp .env.example .env
```

Set:

```env
CRM_API_BASE=https://crm.example.com
WORKER_TOKEN=replace_me
WORKER_NAME=server-dev-worker
MAX_CONCURRENCY=1
JOB_TIMEOUT_SECONDS=60
SCREENSHOT_DIR=./storage/screenshots
```

Run one job:

```bash
python -m audit_worker run-once
```

Run a standalone smoke audit:

```bash
python -m audit_worker audit-url https://example.com
```

## Move to Local Machine

Copy or clone this repository to the local machine, then repeat the same setup inside `worker/`.

Only `.env` changes:

```env
CRM_API_BASE=https://crm.your-domain.example
WORKER_TOKEN=token_created_in_crm
WORKER_NAME=local-worker-1
MAX_CONCURRENCY=1
JOB_TIMEOUT_SECONDS=60
SCREENSHOT_DIR=./storage/screenshots
```

## Browser Automation Later

When Playwright/OpenClaw browser work is added:

```bash
pip install -e ".[browser]"
python -m playwright install chromium
```

Keep concurrency at `1` until the machine is proven stable.

## Production Guardrails

- Use one worker token per machine.
- Revoke tokens for retired machines.
- Keep screenshots private.
- Keep logs local to the worker machine.
- Avoid direct database credentials on worker machines.
- Do not run more than one browser job at a time until monitoring exists.

