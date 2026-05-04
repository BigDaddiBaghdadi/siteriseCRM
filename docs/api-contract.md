# Worker API Contract

The worker communicates with the CRM only over authenticated HTTP.

## Authentication

Every worker request uses a bearer token:

```http
Authorization: Bearer WORKER_TOKEN
Accept: application/json
```

Tokens are created, revoked, and rotated by the CRM.

## Pull Next Job

```http
GET /api/worker/jobs/next
```

When a job is available:

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

When no job is available, the CRM should return `204 No Content`.

## Submit Result

```http
POST /api/worker/jobs/job_123/result
Content-Type: application/json
```

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

## Submit Failure

```http
POST /api/worker/jobs/job_123/fail
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
- The worker never changes lead status except through job result/failure endpoints.
- The CRM owns all durable state.
- The worker may be replaced without database migrations.

