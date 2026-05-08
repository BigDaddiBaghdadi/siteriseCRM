from __future__ import annotations

import hashlib
from typing import Any


DEMO_PATTERNS = [
    {
        "suffix": "Studio",
        "website": None,
        "phone": "+359 88 000 1001",
        "email": "hello@example-demo.local",
        "issue": "No visible website found in the discovery source",
        "recommendation": "Pitch a simple conversion-focused starter website with services, contact, and trust signals",
    },
    {
        "suffix": "Center",
        "website": "http://example.com",
        "phone": "+359 88 000 1002",
        "email": "office@example-demo.local",
        "issue": "Website looks generic and does not clearly sell the business offer",
        "recommendation": "Pitch a redesign with stronger hero copy, local SEO sections, and a clear booking/contact path",
    },
    {
        "suffix": "Clinic",
        "website": "https://example.org",
        "phone": "+359 88 000 1003",
        "email": None,
        "issue": "Contact path is weak and mobile conversion signals are unclear",
        "recommendation": "Pitch mobile-first landing pages with prominent phone and appointment actions",
    },
    {
        "suffix": "Services",
        "website": None,
        "phone": None,
        "email": "contact@example-demo.local",
        "issue": "Business depends on directory presence instead of owned web presence",
        "recommendation": "Pitch a lightweight local credibility site with portfolio, reviews, and lead form",
    },
]


def discover_leads(job: dict[str, Any], provider: str = "demo") -> list[dict[str, Any]]:
    """Discover leads for a CRM discovery job.

    The current implementation intentionally supports a deterministic demo provider.
    Real providers such as Google Places, SerpAPI, DataForSEO, or Apify can plug in
    behind this function without changing the CRM API contract.
    """

    if provider != "demo":
        raise RuntimeError(
            f"Unsupported discovery provider '{provider}'. Set DISCOVERY_PROVIDER=demo or implement a provider adapter."
        )

    return _demo_leads(job)


def _demo_leads(job: dict[str, Any]) -> list[dict[str, Any]]:
    niche = _clean(job.get("niche")) or "Local Business"
    if job.get("random_niche"):
        niche = _stable_random_niche(job)

    city = _clean(job.get("city")) or "Local City"
    country = _clean(job.get("country")) or None
    target = str(job.get("target") or "both")
    limit = max(1, min(int(job.get("result_limit") or 15), 50))

    leads: list[dict[str, Any]] = []
    index = 0
    while len(leads) < limit:
        pattern = DEMO_PATTERNS[index % len(DEMO_PATTERNS)]
        website = pattern["website"]

        if target == "no_website" and website:
            index += 1
            continue
        if target == "needs_redesign" and not website:
            index += 1
            continue

        business_name = f"{city} {niche} {pattern['suffix']} {index + 1}"
        lead: dict[str, Any] = {
            "business_name": business_name,
            "category": niche,
            "city": city,
            "country": country,
            "website_url": website,
            "source": "demo_discovery",
            "source_url": None,
            "phone": pattern["phone"],
            "email": pattern["email"],
            "notes": _pitch_note(target=target, website=website, issue=pattern["issue"]),
        }

        if website:
            lead["audit"] = _demo_audit(business_name, niche, pattern, index)

        leads.append(lead)
        index += 1

    return leads


def _demo_audit(business_name: str, niche: str, pattern: dict[str, Any], index: int) -> dict[str, Any]:
    overall = 48 + (index % 5) * 6
    redesign = min(96, 100 - overall + 38)

    return {
        "business_summary": f"{business_name} appears to be a local {niche.lower()} lead with visible redesign potential.",
        "scores": {
            "overall": overall,
            "redesign": redesign,
            "mobile": max(35, overall - 8),
            "performance": max(30, overall - 12),
            "accessibility": max(40, overall - 6),
            "seo": max(38, overall - 4),
        },
        "issues": [
            pattern["issue"],
            "Homepage message is not strongly pitch or conversion oriented",
            "Local trust signals could be clearer",
        ],
        "recommendations": [
            pattern["recommendation"],
            "Add proof, reviews, service-area content, and stronger contact actions",
        ],
        "contact": {
            "emails": [pattern["email"]] if pattern["email"] else [],
            "phones": [pattern["phone"]] if pattern["phone"] else [],
            "contact_page": None,
        },
        "technology": {
            "cms": None,
            "analytics": False,
            "chat_widget": False,
        },
        "screenshots": {
            "desktop_file": None,
            "mobile_file": None,
        },
    }


def _pitch_note(target: str, website: str | None, issue: str) -> str:
    if not website:
        return f"Pitch angle: business appears to have no owned website. {issue}."
    return f"Pitch angle: existing website can likely be improved. {issue}."


def _stable_random_niche(job: dict[str, Any]) -> str:
    choices = ["Dentists", "Beauty Salons", "Accountants", "Gyms", "Restaurants", "Plumbers", "Law Firms"]
    seed = f"{job.get('job_id')}:{job.get('city')}:{job.get('country')}".encode("utf-8")
    digest = hashlib.sha256(seed).digest()[0]
    return choices[digest % len(choices)]


def _clean(value: Any) -> str | None:
    if value is None:
        return None
    text = str(value).strip()
    return text or None
