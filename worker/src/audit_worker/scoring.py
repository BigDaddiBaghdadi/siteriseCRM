from __future__ import annotations

from typing import Any


def score_signals(signals: dict[str, Any]) -> dict[str, int]:
    seo = 100
    if not signals.get("has_title"):
        seo -= 30
    if not signals.get("has_meta_description"):
        seo -= 25
    if not signals.get("uses_https"):
        seo -= 15

    conversion = 100
    if not signals.get("has_cta_language"):
        conversion -= 35
    if not signals.get("has_contact_link"):
        conversion -= 25
    if not signals.get("has_contact_email") and not signals.get("has_phone"):
        conversion -= 20

    content = 100
    if int(signals.get("text_length", 0)) < 500:
        content -= 35

    seo = _clamp(seo)
    conversion = _clamp(conversion)
    content = _clamp(content)
    overall = round((seo * 0.3) + (conversion * 0.45) + (content * 0.25))
    redesign = _clamp(100 - overall + 35)

    return {
        "overall": overall,
        "redesign": redesign,
        "mobile": 0,
        "performance": 0,
        "accessibility": 0,
        "seo": seo,
    }


def _clamp(value: int) -> int:
    return max(0, min(100, value))

