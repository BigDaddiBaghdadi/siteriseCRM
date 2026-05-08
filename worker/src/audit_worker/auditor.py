from __future__ import annotations

import re
from html.parser import HTMLParser
from typing import Any
from urllib.error import URLError
from urllib.parse import urljoin
from urllib.request import Request, urlopen

from .scoring import score_signals


class _PageSignalParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.title = ""
        self.meta_description = ""
        self.links: list[str] = []
        self.visible_text_parts: list[str] = []
        self._in_title = False
        self._skip_depth = 0

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        attr_map = {key.lower(): value or "" for key, value in attrs}
        if tag in {"script", "style", "noscript"}:
            self._skip_depth += 1
        if tag == "title":
            self._in_title = True
        if tag == "meta" and attr_map.get("name", "").lower() == "description":
            self.meta_description = attr_map.get("content", "").strip()
        if tag == "a" and attr_map.get("href"):
            self.links.append(attr_map["href"])

    def handle_endtag(self, tag: str) -> None:
        if tag in {"script", "style", "noscript"} and self._skip_depth:
            self._skip_depth -= 1
        if tag == "title":
            self._in_title = False

    def handle_data(self, data: str) -> None:
        cleaned = " ".join(data.split())
        if not cleaned:
            return
        if self._in_title:
            self.title = f"{self.title} {cleaned}".strip()
        elif self._skip_depth == 0:
            self.visible_text_parts.append(cleaned)


def audit_url(url: str, timeout_seconds: int = 30) -> dict[str, Any]:
    html = _fetch_html(url, timeout_seconds)
    parser = _PageSignalParser()
    parser.feed(html)

    text = " ".join(parser.visible_text_parts)
    emails = sorted(set(re.findall(r"[\w.+-]+@[\w.-]+\.[a-zA-Z]{2,}", html)))
    phones = sorted(set(re.findall(r"(?:\+?\d[\d\s().-]{7,}\d)", text)))
    contact_links = [
        urljoin(url, href)
        for href in parser.links
        if any(word in href.lower() for word in ["contact", "kontakti", "contacts"])
    ]

    language = _detect_language(text, parser.title, parser.meta_description)

    signals = {
        "has_title": bool(parser.title),
        "has_meta_description": bool(parser.meta_description),
        "has_contact_email": bool(emails),
        "has_phone": bool(phones),
        "has_contact_link": bool(contact_links),
        "has_cta_language": _has_cta_language(text),
        "uses_https": url.lower().startswith("https://"),
        "text_length": len(text),
        "language": language,
    }

    scores = score_signals(signals)

    return {
        "business_summary": _summary_from_page(parser.title, parser.meta_description),
        "scores": scores,
        "issues": _issues_from_signals(signals),
        "recommendations": _recommendations_from_signals(signals),
        "contact": {
            "emails": emails[:5],
            "phones": phones[:5],
            "contact_page": contact_links[0] if contact_links else None,
        },
        "technology": {
            "cms": _detect_cms(html),
            "analytics": "google-analytics" in html.lower() or "gtag(" in html.lower(),
            "chat_widget": "intercom" in html.lower() or "crisp.chat" in html.lower(),
        },
        "screenshots": {
            "desktop_file": None,
            "mobile_file": None,
        },
    }


def _fetch_html(url: str, timeout_seconds: int) -> str:
    request = Request(url, headers={"User-Agent": "website-audit-worker/0.1"})
    try:
        with urlopen(request, timeout=timeout_seconds) as response:
            content_type = response.headers.get("content-type", "")
            if "text/html" not in content_type and "application/xhtml" not in content_type:
                raise RuntimeError(f"Unsupported content type: {content_type}")
            return response.read(2_000_000).decode("utf-8", errors="replace")
    except URLError as exc:
        raise RuntimeError(f"Could not fetch website: {exc.reason}") from exc


def _has_cta_language(text: str) -> bool:
    lowered = text.lower()
    phrases = [
        "book",
        "schedule",
        "contact us",
        "get a quote",
        "request",
        "reserve",
        "запази",
        "контакт",
    ]
    return any(phrase in lowered for phrase in phrases)


def _summary_from_page(title: str, meta_description: str) -> str:
    if meta_description:
        return meta_description[:500]
    if title:
        return title[:250]
    return "Public website with limited machine-readable summary information."


def _detect_language(*parts: str) -> str:
    text = " ".join(parts)
    cyrillic = len(re.findall(r"[А-Яа-я]", text))
    latin = len(re.findall(r"[A-Za-z]", text))
    return "bg" if cyrillic >= max(6, latin // 3) else "en"


def _issues_from_signals(signals: dict[str, Any]) -> list[str]:
    bg = signals.get("language") == "bg"
    issues: list[str] = []
    if not signals["uses_https"]:
        issues.append("Сайтът не използва HTTPS в одитирания URL" if bg else "Website does not use HTTPS in the audited URL")
    if not signals["has_title"]:
        issues.append("Липсва заглавие на страницата" if bg else "Page title is missing")
    if not signals["has_meta_description"]:
        issues.append("Липсва meta описание за търсачките" if bg else "Meta description is missing")
    if not signals["has_cta_language"]:
        issues.append("Основният призив за действие не е достатъчно ясен" if bg else "Primary call to action is unclear")
    if not signals["has_contact_email"] and not signals["has_contact_link"]:
        issues.append("Пътят до контакт е труден за откриване" if bg else "Contact path is hard to detect")
    if signals["text_length"] < 500:
        issues.append("Началната страница изглежда бедна откъм съдържание" if bg else "Homepage content appears thin")
    return issues


def _recommendations_from_signals(signals: dict[str, Any]) -> list[str]:
    bg = signals.get("language") == "bg"
    recommendations: list[str] = []
    if not signals["has_cta_language"]:
        recommendations.append("Добавете ясен призив за действие още в първия екран" if bg else "Add a clear homepage call to action")
    if not signals["has_contact_link"]:
        recommendations.append("Направете страницата за контакт лесна за намиране" if bg else "Make the contact page easy to find")
    if not signals["has_meta_description"]:
        recommendations.append("Добавете кратко и ясно SEO meta описание" if bg else "Add a concise SEO meta description")
    if signals["text_length"] < 500:
        recommendations.append("Добавете по-ясно съдържание за услуги, доверие и предимства" if bg else "Add clearer service and trust content")
    return recommendations


def _detect_cms(html: str) -> str | None:
    lowered = html.lower()
    if "wp-content" in lowered or "wordpress" in lowered:
        return "WordPress"
    if "shopify" in lowered:
        return "Shopify"
    if "wixstatic" in lowered:
        return "Wix"
    if "squarespace" in lowered:
        return "Squarespace"
    return None

