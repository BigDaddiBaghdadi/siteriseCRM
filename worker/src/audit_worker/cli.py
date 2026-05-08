from __future__ import annotations

import argparse
import json
import sys

from .api import WorkerApiClient
from .auditor import audit_url
from .config import WorkerConfig
from .discoverer import discover_leads


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="audit-worker")
    subparsers = parser.add_subparsers(dest="command", required=True)

    subparsers.add_parser("run-once", help="Pull and process one CRM audit job.")
    subparsers.add_parser("run-discovery-once", help="Pull and process one CRM lead discovery job.")

    audit_url_parser = subparsers.add_parser("audit-url", help="Audit a single URL without CRM.")
    audit_url_parser.add_argument("url")

    args = parser.parse_args(argv)

    if args.command == "audit-url":
        result = audit_url(args.url)
        print(json.dumps(result, indent=2, sort_keys=True))
        return 0

    if args.command == "run-once":
        return _run_once()

    if args.command == "run-discovery-once":
        return _run_discovery_once()

    return 2


def _client_from_config(config: WorkerConfig) -> WorkerApiClient:
    return WorkerApiClient(
        base_url=config.crm_api_base,
        token=config.worker_token,
        timeout_seconds=config.job_timeout_seconds,
    )


def _run_once() -> int:
    config = WorkerConfig.from_env()
    client = _client_from_config(config)

    job = client.next_job()
    if job is None:
        print("No audit jobs available.")
        return 0

    job_id = str(job["job_id"])
    url = str(job["website_url"])

    try:
        result = audit_url(url, timeout_seconds=config.job_timeout_seconds)
        result["lead_id"] = job["lead_id"]
        client.submit_result(job_id, result)
        print(f"Submitted audit result for job {job_id}.")
        return 0
    except Exception as exc:
        client.submit_failure(job_id, str(exc), retryable=True)
        print(f"Audit failed for job {job_id}: {exc}", file=sys.stderr)
        return 1


def _run_discovery_once() -> int:
    config = WorkerConfig.from_env()
    client = _client_from_config(config)

    job = client.next_discovery_job()
    if job is None:
        print("No lead discovery jobs available.")
        return 0

    job_id = str(job["job_id"])

    try:
        leads = discover_leads(job, provider=config.discovery_provider)
        client.submit_discovery_result(job_id, leads)
        print(f"Submitted {len(leads)} discovery leads for job {job_id}.")
        return 0
    except Exception as exc:
        client.submit_discovery_failure(job_id, str(exc), retryable=True)
        print(f"Discovery failed for job {job_id}: {exc}", file=sys.stderr)
        return 1

