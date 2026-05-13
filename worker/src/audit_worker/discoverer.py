from __future__ import annotations

import hashlib
import json
from typing import Any
from urllib.parse import quote_plus, urlencode
from urllib.request import Request, urlopen

from .auditor import audit_url
from .snapshots import capture_snapshots_and_redesign


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

NICHE_TAGS: dict[str, list[tuple[str, str]]] = {
    "dentist": [("amenity", "dentist")],
    "dentists": [("amenity", "dentist")],
    "beauty": [("shop", "beauty"), ("shop", "hairdresser")],
    "beauty salons": [("shop", "beauty"), ("shop", "hairdresser")],
    "hairdresser": [("shop", "hairdresser")],
    "gyms": [("leisure", "fitness_centre"), ("sport", "fitness")],
    "gym": [("leisure", "fitness_centre"), ("sport", "fitness")],
    "restaurants": [("amenity", "restaurant"), ("amenity", "fast_food"), ("amenity", "cafe")],
    "restaurant": [("amenity", "restaurant")],
    "plumbers": [("craft", "plumber")],
    "plumber": [("craft", "plumber")],
    "law firms": [("office", "lawyer")],
    "lawyer": [("office", "lawyer")],
    "accountants": [("office", "accountant")],
    "accountant": [("office", "accountant")],
}


class DiscoveryError(RuntimeError):
    pass


def discover_leads(job: dict[str, Any], provider: str = "demo") -> list[dict[str, Any]]:
    """Discover leads for a CRM discovery job."""

    provider = provider.lower().strip()
    if provider == "demo":
        return _demo_leads(job)
    if provider == "osm":
        return _osm_leads(job)

    raise RuntimeError(
        f"Unsupported discovery provider '{provider}'. Use DISCOVERY_PROVIDER=demo or DISCOVERY_PROVIDER=osm."
    )


def _osm_leads(job: dict[str, Any]) -> list[dict[str, Any]]:
    niche = _clean(job.get("niche")) or _stable_random_niche(job)
    if job.get("random_niche"):
        niche = _stable_random_niche(job)

    city = _clean(job.get("city"))
    if not city:
        raise DiscoveryError("OSM discovery requires a city.")

    country = _clean(job.get("country"))
    target = str(job.get("target") or "needs_redesign")
    limit = max(1, min(int(job.get("result_limit") or 15), 50))
    tags = _tags_for_niche(niche)
    area_id = _nominatim_area_id(city=city, country=country)
    elements = _overpass_businesses(area_id=area_id, tags=tags, limit=limit * 10)

    leads: list[dict[str, Any]] = []
    seen_names: set[str] = set()

    for element in elements:
        tags_map = element.get("tags") or {}
        business_name = _clean(tags_map.get("name"))
        if not business_name or business_name.lower() in seen_names:
            continue

        website = _normalize_url(_first_present(tags_map, ["website", "contact:website", "url"]))

        if target == "no_website" and website:
            continue
        if target == "needs_redesign" and not website:
            continue

        seen_names.add(business_name.lower())
        source_url = f"https://www.openstreetmap.org/{element.get('type', 'node')}/{element.get('id')}"
        phone = _first_present(tags_map, ["phone", "contact:phone", "mobile", "contact:mobile"])
        email = _first_present(tags_map, ["email", "contact:email"])
        category = _category_from_tags(tags_map, fallback=niche)

        lead: dict[str, Any] = {
            "business_name": business_name,
            "category": category,
            "city": city,
            "country": country,
            "website_url": website,
            "source": "openstreetmap",
            "source_url": source_url,
            "phone": phone,
            "email": email,
            "notes": _osm_pitch_note(website=website, source_url=source_url),
        }

        if website:
            try:
                audit = audit_url(website, timeout_seconds=12)
                audit.update(capture_snapshots_and_redesign(website, business_name, audit, timeout_seconds=20))
                lead["audit"] = audit
            except Exception:
                # Keep generated website leads high quality: real website plus
                # audit, snapshots, and redesign mockup.
                continue

        leads.append(lead)
        if len(leads) >= limit:
            break

    if not leads:
        raise DiscoveryError(f"No OSM website leads found for {niche} in {city} matching target={target}.")

    return leads


def _nominatim_area_id(city: str, country: str | None) -> int:
    query = city if not country else f"{city}, {country}"
    url = "https://nominatim.openstreetmap.org/search?" + urlencode(
        {"q": query, "format": "jsonv2", "limit": "1"}
    )
    data = _json_get(url)
    if not data:
        raise DiscoveryError(f"Could not geocode city with Nominatim: {query}")

    osm_type = data[0].get("osm_type")
    osm_id = int(data[0].get("osm_id"))
    if osm_type == "relation":
        return 3_600_000_000 + osm_id
    if osm_type == "way":
        return 2_400_000_000 + osm_id
    raise DiscoveryError(f"Nominatim returned unsupported OSM type for area lookup: {osm_type}")


def _overpass_businesses(area_id: int, tags: list[tuple[str, str]], limit: int) -> list[dict[str, Any]]:
    selectors = []
    for key, value in tags:
        safe_key = key.replace('"', '')
        safe_value = value.replace('"', '')
        selectors.extend(
            [
                f'node["{safe_key}"="{safe_value}"]["name"](area.searchArea);',
                f'way["{safe_key}"="{safe_value}"]["name"](area.searchArea);',
                f'relation["{safe_key}"="{safe_value}"]["name"](area.searchArea);',
            ]
        )

    query = f"""
[out:json][timeout:25];
area({area_id})->.searchArea;
(
{chr(10).join(selectors)}
);
out tags center {limit};
"""
    url = "https://overpass-api.de/api/interpreter?data=" + quote_plus(query)
    payload = _json_get(url, timeout_seconds=35)
    return payload.get("elements", [])


def _json_get(url: str, timeout_seconds: int = 20) -> Any:
    request = Request(
        url,
        headers={
            "User-Agent": "siterisecrm-worker/0.1 (lead discovery; contact: alan@windmanifest.com)",
            "Accept": "application/json",
        },
    )
    with urlopen(request, timeout=timeout_seconds) as response:
        return json.loads(response.read().decode("utf-8"))


def _tags_for_niche(niche: str) -> list[tuple[str, str]]:
    lowered = niche.lower().strip()
    if lowered in NICHE_TAGS:
        return NICHE_TAGS[lowered]
    for key, tags in NICHE_TAGS.items():
        if key in lowered or lowered in key:
            return tags
    raise DiscoveryError(
        f"No OSM tag mapping for niche '{niche}'. Try Dentists, Beauty Salons, Gyms, Restaurants, Plumbers, Law Firms, or Accountants."
    )


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


def _osm_pitch_note(website: str | None, source_url: str) -> str:
    if not website:
        return f"Pitch angle: OpenStreetMap lists this business without a website. Source: {source_url}."
    return f"Pitch angle: business has a listed website; audit signals can support a redesign pitch. Source: {source_url}."


def _stable_random_niche(job: dict[str, Any]) -> str:
    choices = ["Dentists", "Beauty Salons", "Accountants", "Gyms", "Restaurants", "Plumbers", "Law Firms"]
    seed = f"{job.get('job_id')}:{job.get('city')}:{job.get('country')}".encode("utf-8")
    digest = hashlib.sha256(seed).digest()[0]
    return choices[digest % len(choices)]


def _category_from_tags(tags: dict[str, Any], fallback: str) -> str:
    for key in ["amenity", "shop", "craft", "office", "leisure", "sport"]:
        if tags.get(key):
            return str(tags[key]).replace("_", " ").title()
    return fallback


def _normalize_url(value: str | None) -> str | None:
    if not value:
        return None
    if value.startswith(('http://', 'https://')):
        return value
    return f'https://{value}'


def _first_present(tags: dict[str, Any], keys: list[str]) -> str | None:
    for key in keys:
        value = _clean(tags.get(key))
        if value:
            return value
    return None


def _clean(value: Any) -> str | None:
    if value is None:
        return None
    text = str(value).strip()
    return text or None
