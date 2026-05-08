from __future__ import annotations

import base64
import html
import re
from typing import Any
from urllib.parse import urlparse


def capture_snapshots_and_redesign(url: str, business_name: str, audit: dict[str, Any], timeout_seconds: int) -> dict[str, Any]:
    """Capture current-site screenshots and generate a pitch mockup.

    If Playwright/browser capture fails, this still returns a redesign concept/mockup so
    the CRM can compare the current audit notes against a proposed direction.
    """

    concept = _redesign_concept(url, business_name, audit)
    result: dict[str, Any] = {
        "redesign": {
            "concept": concept,
            "html_base64": _base64_text(_mockup_html(url, business_name, concept)),
        }
    }

    try:
        from playwright.sync_api import sync_playwright

        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True)
            page = browser.new_page(viewport={"width": 1440, "height": 1200}, device_scale_factor=1)
            page.goto(url, wait_until="networkidle", timeout=timeout_seconds * 1000)
            result.setdefault("screenshots", {})["desktop_base64"] = base64.b64encode(
                page.screenshot(full_page=True, type="png")
            ).decode("ascii")

            mobile = browser.new_page(
                viewport={"width": 390, "height": 900},
                device_scale_factor=2,
                is_mobile=True,
            )
            mobile.goto(url, wait_until="networkidle", timeout=timeout_seconds * 1000)
            result.setdefault("screenshots", {})["mobile_base64"] = base64.b64encode(
                mobile.screenshot(full_page=True, type="png")
            ).decode("ascii")
            browser.close()
    except Exception as exc:
        result.setdefault("screenshots", {})["capture_error"] = str(exc)

    return result


def _redesign_concept(url: str, business_name: str, audit: dict[str, Any]) -> dict[str, Any]:
    host = urlparse(url).netloc or business_name
    issues = audit.get("issues") or []
    recommendations = audit.get("recommendations") or []
    summary = audit.get("business_summary") or f"{business_name} can benefit from a clearer, conversion-focused website."
    palette = _palette_from_name(business_name)

    return {
        "hero_copy": f"A clearer, faster website for {business_name} that turns local visitors into calls and bookings.",
        "subcopy": summary[:260],
        "style_notes": [
            "Keep recognizable brand cues while making the first screen cleaner and more conversion-focused.",
            "Use a high-contrast hero, clear service cards, trust proof, and sticky contact actions.",
            "Design mobile-first so phone, booking, and location actions are immediately visible.",
        ],
        "priority_fixes": recommendations[:5] or issues[:5],
        "palette": palette,
        "source_host": host,
    }


def _mockup_html(url: str, business_name: str, concept: dict[str, Any]) -> str:
    palette = concept["palette"]
    services = concept.get("priority_fixes") or [
        "Clarify the main offer",
        "Improve trust and reviews",
        "Make contact actions easier",
    ]
    service_cards = "".join(
        f"<div class='card'><span>0{i}</span><strong>{html.escape(str(item))}</strong></div>"
        for i, item in enumerate(services[:4], start=1)
    )

    return f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{html.escape(business_name)} redesign concept</title>
<style>
:root {{ --primary: {palette['primary']}; --accent: {palette['accent']}; --dark: #111827; --muted: #64748b; --bg: #f8fafc; }}
* {{ box-sizing: border-box; }}
body {{ margin: 0; font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif; color: var(--dark); background: var(--bg); }}
.header {{ display:flex; justify-content:space-between; align-items:center; padding:24px clamp(20px,5vw,72px); background:white; border-bottom:1px solid #e5e7eb; position:sticky; top:0; z-index:2; }}
.logo {{ font-weight:900; letter-spacing:-.04em; font-size:22px; }}
.nav {{ display:flex; gap:20px; color:var(--muted); font-weight:700; }}
.cta {{ background:var(--primary); color:white; padding:12px 18px; border-radius:999px; text-decoration:none; font-weight:900; }}
.hero {{ display:grid; grid-template-columns:1.1fr .9fr; gap:40px; padding:72px clamp(20px,5vw,72px); align-items:center; background:linear-gradient(135deg, white, {palette['soft']}); }}
.eyebrow {{ color:var(--primary); text-transform:uppercase; font-weight:900; letter-spacing:.14em; font-size:12px; }}
h1 {{ font-size:clamp(42px,7vw,76px); line-height:.94; letter-spacing:-.07em; margin:12px 0 18px; }}
.lede {{ font-size:20px; color:#475569; max-width:680px; }}
.hero-panel {{ background:var(--dark); color:white; border-radius:28px; padding:30px; min-height:360px; display:grid; align-content:end; box-shadow:0 30px 80px rgba(15,23,42,.22); }}
.hero-panel strong {{ font-size:32px; line-height:1; }}
.hero-panel p {{ color:#cbd5e1; }}
.cards {{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; padding:40px clamp(20px,5vw,72px); }}
.card {{ background:white; border:1px solid #e5e7eb; border-radius:20px; padding:22px; min-height:160px; box-shadow:0 14px 40px rgba(15,23,42,.06); }}
.card span {{ color:var(--accent); font-weight:900; }}
.card strong {{ display:block; margin-top:16px; font-size:18px; }}
.band {{ margin:24px clamp(20px,5vw,72px) 72px; border-radius:28px; background:var(--primary); color:white; padding:36px; display:flex; justify-content:space-between; gap:24px; align-items:center; }}
.band h2 {{ margin:0; font-size:34px; letter-spacing:-.04em; }}
@media (max-width: 850px) {{ .hero, .cards {{ grid-template-columns:1fr; }} .nav {{ display:none; }} .band {{ flex-direction:column; align-items:flex-start; }} }}
</style>
</head>
<body>
<header class="header"><div class="logo">{html.escape(business_name)}</div><nav class="nav"><span>Services</span><span>Reviews</span><span>Contact</span></nav><a class="cta" href="{html.escape(url)}">Call / Book</a></header>
<main>
<section class="hero"><div><div class="eyebrow">Redesign concept · {html.escape(str(concept.get('source_host', '')))}</div><h1>{html.escape(concept['hero_copy'])}</h1><p class="lede">{html.escape(concept.get('subcopy', ''))}</p><p><a class="cta" href="{html.escape(url)}">Get a quote today</a></p></div><div class="hero-panel"><strong>Modern local trust, clearer services, faster contact.</strong><p>Designed to make the value obvious in the first 5 seconds and convert more mobile visitors.</p></div></section>
<section class="cards">{service_cards}</section>
<section class="band"><h2>From brochure site to lead machine.</h2><a class="cta" href="{html.escape(url)}">Visit current site</a></section>
</main>
</body>
</html>"""


def _palette_from_name(name: str) -> dict[str, str]:
    palettes = [
        {"primary": "#0f766e", "accent": "#f59e0b", "soft": "#ccfbf1"},
        {"primary": "#4f46e5", "accent": "#ec4899", "soft": "#e0e7ff"},
        {"primary": "#0f172a", "accent": "#22c55e", "soft": "#dcfce7"},
        {"primary": "#be123c", "accent": "#f97316", "soft": "#ffe4e6"},
    ]
    digest = hashlib_int(name)
    return palettes[digest % len(palettes)]


def hashlib_int(text: str) -> int:
    return int(re.sub(r"\D", "", str(abs(hash(text))))[:8] or "0")


def _base64_text(text: str) -> str:
    return base64.b64encode(text.encode("utf-8")).decode("ascii")
