# Worker API Contract

The worker communicates with the CRM only over authenticated HTTP.

## Authentication

Every worker request uses a bearer token:

```http
Authorization: Bearer WORKER_TOKEN
Accept: application/json
```

Tokens are created, revoked, and rotated by the CRM.

## Lead Discovery Jobs

Lead discovery jobs are created from the CRM Lead Discovery page. The worker pulls them, searches for businesses, analyzes opportunities, and submits pitch-ready lead cards.

### Pull Next Discovery Job

```http
GET /api/worker/discovery-jobs/next
```

When a job is available:

```json
{
  "job_id": "123",
  "niche": "Dentists",
  "random_niche": false,
  "city": "Sofia",
  "country": "Bulgaria",
  "result_limit": 15,
  "target": "needs_redesign"
}
```

`target` values:

- `no_website` — find businesses where no website is visible
- `needs_redesign` — find businesses with weak/outdated websites
- `both` — return both opportunity types

When no discovery job is available, return `204 No Content`.

### Submit Discovery Results

```http
POST /api/worker/discovery-jobs/123/result
Content-Type: application/json
```

```json
{
  "leads": [
    {
      "business_name": "Example Clinic",
      "category": "Dentist",
      "city": "Sofia",
      "country": "Bulgaria",
      "website_url": "https://example.com",
      "source": "google_maps",
      "source_url": "https://maps.example/result",
      "phone": "+359...",
      "email": "office@example.com",
      "notes": "Good redesign candidate for appointment-booking pitch.",
      "audit": {
        "business_summary": "Dental clinic with cosmetic and emergency services.",
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
          "Primary call to action is unclear"
        ],
        "recommendations": [
          "Create a booking-focused homepage",
          "Improve mobile layout"
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
          "desktop_file": "screenshots/example_desktop.png",
          "mobile_file": "screenshots/example_mobile.png"
        }
      }
    }
  ]
}
```

For no-website opportunities, `website_url` and `audit` may be omitted. The CRM will still create a lead card with contact/source details and mark the website as missing.

### Submit Discovery Failure

```http
POST /api/worker/discovery-jobs/123/fail
Content-Type: application/json
```

```json
{
  "error": "Search provider timed out",
  "retryable": true
}
```

## Website Audit Jobs

Audit jobs are created for known leads that already have a website URL.

### Pull Next Audit Job

```http
GET /api/worker/jobs/next
```

When a job is available:

```json
{
  "job_id": "123",
  "lead_id": "456",
  "business_name": "Example Clinic",
  "website_url": "https://example.com",
  "category": "Dentist",
  "city": "Sofia"
}
```

When no job is available, the CRM should return `204 No Content`.

### Submit Audit Result

```http
POST /api/worker/jobs/123/result
Content-Type: application/json
```

```json
{
  "lead_id": "456",
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
    "Primary call to action is unclear"
  ],
  "recommendations": [
    "Create a booking-focused homepage",
    "Improve mobile layout"
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

The CRM stores the result, marks the job `audited`, and moves the lead to review.

### Submit Audit Failure

```http
POST /api/worker/jobs/123/fail
Content-Type: application/json
```

```json
{
  "error": "Navigation timeout after 30 seconds",
  "retryable": true
}
```

The CRM increments attempts and either requeues or marks the job failed.

## Worker Constraints

- The worker never connects to the CRM database.
- The worker never sends outreach messages.
- The worker never owns final lead status or review decisions.
- The CRM owns all durable state.
- The worker may be replaced without database migrations.
