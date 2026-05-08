from __future__ import annotations

import json
from dataclasses import dataclass
from typing import Any
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


class WorkerApiError(RuntimeError):
    pass


@dataclass(frozen=True)
class WorkerApiClient:
    base_url: str
    token: str
    timeout_seconds: int

    def next_job(self) -> dict[str, Any] | None:
        try:
            return self._request("GET", "/api/worker/jobs/next")
        except WorkerApiError as exc:
            if "HTTP 404" in str(exc) or "HTTP 204" in str(exc):
                return None
            raise

    def submit_result(self, job_id: str, payload: dict[str, Any]) -> dict[str, Any]:
        return self._request("POST", f"/api/worker/jobs/{job_id}/result", payload)

    def submit_failure(self, job_id: str, error: str, retryable: bool = True) -> dict[str, Any]:
        return self._request(
            "POST",
            f"/api/worker/jobs/{job_id}/fail",
            {"error": error, "retryable": retryable},
        )

    def next_discovery_job(self) -> dict[str, Any] | None:
        try:
            return self._request("GET", "/api/worker/discovery-jobs/next")
        except WorkerApiError as exc:
            if "HTTP 404" in str(exc) or "HTTP 204" in str(exc):
                return None
            raise

    def submit_discovery_result(self, job_id: str, leads: list[dict[str, Any]]) -> dict[str, Any]:
        return self._request("POST", f"/api/worker/discovery-jobs/{job_id}/result", {"leads": leads})

    def submit_discovery_failure(self, job_id: str, error: str, retryable: bool = True) -> dict[str, Any]:
        return self._request(
            "POST",
            f"/api/worker/discovery-jobs/{job_id}/fail",
            {"error": error, "retryable": retryable},
        )

    def _request(
        self,
        method: str,
        path: str,
        payload: dict[str, Any] | None = None,
    ) -> dict[str, Any]:
        url = f"{self.base_url.rstrip('/')}{path}"
        body = None
        headers = {
            "Authorization": f"Bearer {self.token}",
            "Accept": "application/json",
            "User-Agent": "website-audit-worker/0.1",
        }

        if payload is not None:
            body = json.dumps(payload).encode("utf-8")
            headers["Content-Type"] = "application/json"

        request = Request(url, data=body, headers=headers, method=method)

        try:
            with urlopen(request, timeout=self.timeout_seconds) as response:
                raw = response.read()
                if not raw:
                    return {}
                return json.loads(raw.decode("utf-8"))
        except HTTPError as exc:
            detail = exc.read().decode("utf-8", errors="replace")
            raise WorkerApiError(f"HTTP {exc.code} from CRM API: {detail}") from exc
        except URLError as exc:
            raise WorkerApiError(f"Could not reach CRM API: {exc.reason}") from exc
        except json.JSONDecodeError as exc:
            raise WorkerApiError("CRM API returned invalid JSON") from exc

