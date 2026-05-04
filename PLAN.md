# Website Audit CRM and Portable Scraper Plan

## Objective

Build a private CRM system on this server for collecting business website leads, auditing those websites, scoring whether they need a redesign or update, and managing follow-up workflow.

Prepare the scraping/audit system on this server during development, but package it so it can later run on a local machine or separate worker without changing the CRM.

This project does not depend on any existing public website. It should operate as a standalone internal system.

## Core Split

```text
CRM Server
    - Lead database
    - Audit database
    - Admin dashboard
    - Worker API
    - Review and approval workflow
    - Export/reporting

Portable Scraper Worker
    - Pulls audit jobs from CRM
    - Visits public business websites
    - Captures screenshots
    - Extracts public contact details
    - Scores website quality
    - Sends structured results back to CRM
```

## Deployment Model

### Phase 1 Development

Both pieces can be developed on this server:

- CRM runs as the main web app.
- Worker runs manually or as a controlled background process.
- Worker concurrency stays at `1` during testing.
- Browser automation is treated as disposable and isolated.

### Phase 2 Production-Like Use

CRM remains on this server.

Worker moves to a local machine or separate worker host:

- Same worker codebase.
- Same `.env` structure.
- Same API token authentication.
- Same job polling and result submission endpoints.
- No direct database access from the worker.

## Server Responsibilities

The server should own all durable business state:

- Leads
- Lead sources
- Audit jobs
- Audit results
- Screenshots or screenshot metadata
- Website scores
- Contact details
- Notes
- Status pipeline
- Review decisions
- Exports
- User accounts
- API tokens

The server should not require a browser to function.

## Worker Responsibilities

The worker should be portable and stateless except for temporary files:

- Poll the CRM for the next job.
- Visit the target website.
- Respect timeout and retry limits.
- Capture desktop and mobile screenshots.
- Extract visible public contact information.
- Detect basic technology signals.
- Check obvious UX/design/update issues.
- Calculate first-pass audit scores.
- Submit JSON results back to the CRM.
- Upload screenshots or send them to server storage.

The worker should not:

- Send outreach messages.
- Own lead status.
- Modify CRM records directly through the database.
- Store permanent business data locally.
- Depend on this server's filesystem paths.
- Depend on a public marketing website.

## Recommended Stack

### CRM

Use a conventional server-side app because the product is mostly admin workflows and records.

Recommended option:

- Laravel
- MariaDB or PostgreSQL
- Redis for queues/cache
- LiteSpeed reverse proxy or virtual host
- Server-rendered admin UI

Alternative:

- FastAPI
- PostgreSQL
- Redis/RQ or Celery
- Simple admin frontend

Laravel is the practical default on this server because PHP, databases, Redis, and LiteSpeed are already present.

### Worker

Recommended:

- Python
- Playwright or browser automation used by OpenClaw
- Requests/httpx for API communication
- Local `.env`
- Systemd service or Docker later

The worker should live in its own folder so it can be copied or cloned onto a local machine.

## Suggested Project Layout

```text
/root/website-audit-crm/
    PLAN.md
    crm/
        app code
        database migrations
        admin dashboard
        worker API
    worker/
        scraper/auditor code
        browser automation
        scoring logic
        screenshot handling
        .env.example
        README.md
    docs/
        api-contract.md
        worker-deployment.md
        scoring-rules.md
```

## CRM Pipeline

Lead statuses:

- new
- queued_for_audit
- auditing
- audit_failed
- audited
- needs_review
- approved
- rejected
- exported
- archived

The MVP should focus on finding and reviewing website opportunities, not automated outreach.

## Data Model Draft

### users

- id
- name
- email
- password_hash
- role
- created_at
- updated_at

### leads

- id
- business_name
- category
- city
- country
- website_url
- source
- source_url
- phone
- email
- status
- notes
- created_at
- updated_at

### audit_jobs

- id
- lead_id
- status
- priority
- attempts
- locked_by
- locked_at
- last_error
- created_at
- updated_at

### audits

- id
- lead_id
- audit_job_id
- business_summary
- overall_score
- redesign_score
- mobile_score
- performance_score
- accessibility_score
- seo_score
- issues_json
- recommendations_json
- technology_json
- contact_json
- desktop_screenshot_path
- mobile_screenshot_path
- created_at

### worker_tokens

- id
- name
- token_hash
- active
- last_used_at
- created_at

## Worker API Contract

### Pull next job

```http
GET /api/worker/jobs/next
Authorization: Bearer WORKER_TOKEN
```

Response:

```json
{
  "job_id": "job_123",
  "lead_id": "lead_456",
  "business_name": "Example Clinic",
  "website_url": "https://example.com",
  "category": "Dentist",
  "city": "Sofia"
}
```

### Submit successful audit

```http
POST /api/worker/jobs/job_123/result
Authorization: Bearer WORKER_TOKEN
Content-Type: application/json
```

Body:

```json
{
  "lead_id": "lead_456",
  "business_summary": "Private clinic with dental and cosmetic services.",
  "scores": {
    "overall": 61,
    "redesign": 84,
    "mobile": 52,
    "performance": 48,
    "accessibility": 58,
    "seo": 66
  },
  "issues": [
    "Mobile text is difficult to read",
    "Primary call to action is unclear",
    "Images look dated",
    "No obvious booking path"
  ],
  "recommendations": [
    "Create a booking-focused homepage",
    "Improve mobile layout",
    "Replace outdated imagery",
    "Add clearer service pages"
  ],
  "contact": {
    "emails": ["office@example.com"],
    "phones": ["+359..."],
    "contact_page": "https://example.com/contact"
  },
  "technology": {
    "cms": "WordPress",
    "analytics": true,
    "chat_widget": false
  },
  "screenshots": {
    "desktop_file": "job_123_desktop.png",
    "mobile_file": "job_123_mobile.png"
  }
}
```

### Submit failed audit

```http
POST /api/worker/jobs/job_123/fail
Authorization: Bearer WORKER_TOKEN
Content-Type: application/json
```

Body:

```json
{
  "error": "Navigation timeout after 30 seconds",
  "retryable": true
}
```

## Scoring Rules MVP

Start with explainable heuristics before adding heavier AI judgment.

Score signals:

- Mobile usability
- Page speed estimate
- Clear headline
- Clear call to action
- Contact visibility
- Booking/contact flow
- Visual age or dated styling
- Broken layout
- HTTPS availability
- Basic SEO metadata
- Accessibility basics
- Content clarity
- Technology age signals

The audit should store both scores and human-readable reasons.

## AI Usage

AI should be used after the first-pass crawl:

- Summarize what the business does.
- Turn detected issues into readable audit notes.
- Suggest redesign/update recommendations.
- Generate a short internal review summary.

AI should not be required for basic job execution. If AI fails, the audit job should still store screenshots, extracted metadata, and heuristic scores.

## Screenshot Storage

MVP options:

- Store screenshots on this server under CRM-managed storage.
- Store only relative paths in the database.
- Keep screenshots private behind authenticated admin routes.

Do not expose screenshots publicly by default.

## Compliance and Safety

- Only inspect public websites.
- Do not log into websites.
- Do not scrape private or authenticated areas.
- Respect robots.txt and site terms where applicable.
- Use low crawl rates.
- Store the source of every lead.
- Store why a website was reviewed.
- Avoid collecting unnecessary personal data.
- Keep opt-out/suppression support available if outreach is added later.

## Build Order

1. Create neutral project structure.
2. Build CRM skeleton with authentication.
3. Add lead CRUD and CSV import.
4. Add audit job table and queue controls.
5. Add worker token authentication.
6. Add worker API endpoints.
7. Build portable worker skeleton.
8. Add browser visit and screenshot capture.
9. Add basic extraction and scoring.
10. Add audit result storage in CRM.
11. Add admin audit review screen.
12. Add export functionality.
13. Add local-machine worker deployment docs.
14. Add optional AI summary/recommendation step.

## MVP Success Criteria

- Import a small list of leads.
- Queue audits from the CRM.
- Run the worker on this server with concurrency `1`.
- Capture desktop and mobile screenshots.
- Store structured audit results.
- Review audited leads in the CRM.
- Export approved opportunities.
- Move the worker to a local machine without changing CRM code.

## Local Worker Transfer Requirements

Before moving the worker, confirm:

- Worker has no hardcoded server paths.
- Worker only talks to CRM through HTTP API.
- Worker uses `.env` for CRM URL and token.
- Browser dependencies are documented.
- Screenshot upload path is API-based.
- Logs are local to the worker machine.
- CRM can revoke and rotate worker tokens.

Example worker `.env`:

```env
CRM_API_BASE=https://crm.example.com
WORKER_TOKEN=replace_me
WORKER_NAME=local-worker-1
MAX_CONCURRENCY=1
JOB_TIMEOUT_SECONDS=60
SCREENSHOT_DIR=./storage/screenshots
```

## Final Target Architecture

```text
Admin user
    -> CRM dashboard on this server
    -> imports leads and queues audits

Local worker machine
    -> polls CRM API
    -> audits public websites
    -> uploads results

CRM server
    -> stores all durable data
    -> displays screenshots and scores
    -> manages review workflow
    -> exports selected opportunities
```

