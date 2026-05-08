from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class WorkerConfig:
    crm_api_base: str
    worker_token: str
    worker_name: str
    max_concurrency: int
    job_timeout_seconds: int
    screenshot_dir: str
    discovery_provider: str
    poll_interval_seconds: int

    @classmethod
    def from_env(cls) -> "WorkerConfig":
        _load_dotenv()

        return cls(
            crm_api_base=_required("CRM_API_BASE"),
            worker_token=_required("WORKER_TOKEN"),
            worker_name=os.getenv("WORKER_NAME", "local-worker-1"),
            max_concurrency=int(os.getenv("MAX_CONCURRENCY", "1")),
            job_timeout_seconds=int(os.getenv("JOB_TIMEOUT_SECONDS", "60")),
            screenshot_dir=os.getenv("SCREENSHOT_DIR", "./storage/screenshots"),
            discovery_provider=os.getenv("DISCOVERY_PROVIDER", "demo"),
            poll_interval_seconds=int(os.getenv("POLL_INTERVAL_SECONDS", "30")),
        )


def _load_dotenv(path: str = ".env") -> None:
    env_path = Path(path)
    if not env_path.exists():
        return

    for raw_line in env_path.read_text(encoding="utf-8-sig").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        key = key.strip()
        value = value.strip().strip('"').strip("'")
        os.environ.setdefault(key, value)


def _required(name: str) -> str:
    value = os.getenv(name)
    if not value:
        raise RuntimeError(f"Missing required environment variable: {name}")
    return value

