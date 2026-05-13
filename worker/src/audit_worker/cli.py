from __future__ import annotations

import argparse
import json
import sys
import time

from .api import WorkerApiClient
from .auditor import audit_url
from .config import WorkerConfig
from .discoverer import discover_leads
from .snapshots import capture_snapshots_and_redesign


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="audit-worker")
    subparsers = parser.add_subparsers(dest="command", required=True)

    subparsers.add_parser("run-once", help="Pull and process one CRM audit job.")
    subparsers.add_parser("run-discovery-once", help="Pull and process one CRM lead discovery job.")

    work_parser = subparsers.add_parser("work", help="Continuously poll and process CRM jobs.")
    work_parser.add_argument(
        "--max-cycles",
        type=int,
        default=None,
        help="Stop after this many polling cycles. Useful for smoke tests.",
    )

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

    if args.command == "work":
        return _work(max_cycles=args.max_cycles)

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

    processed = _process_audit_job(client, config)
    if not processed:
        print("No audit jobs available.")
    return 0


def _run_discovery_once() -> int:
    config = WorkerConfig.from_env()
    client = _client_from_config(config)

    processed = _process_discovery_job(client, config)
    if not processed:
        print("No lead discovery jobs available.")
    return 0


def _work(max_cycles: int | None = None) -> int:
    config = WorkerConfig.from_env()
    client = _client_from_config(config)
    cycle = 0

    print(
        f"Worker '{config.worker_name}' started. "
        f"CRM={config.crm_api_base}, discovery_provider={config.discovery_provider}, "
        f"poll_interval={config.poll_interval_seconds}s"
    )

    while True:
        cycle += 1
        did_work = False

        try:
            did_work = _process_discovery_job(client, config) or did_work
            did_work = _process_audit_job(client, config) or did_work
        except KeyboardInterrupt:
            print("Worker stopped.")
            return 0
        except Exception as exc:
            print(f"Worker cycle failed: {exc}", file=sys.stderr)

        if max_cycles is not None and cycle >= max_cycles:
            print(f"Worker stopped after {cycle} cycle(s).")
            return 0

        if not did_work:
            time.sleep(config.poll_interval_seconds)


def _process_audit_job(client: WorkerApiClient, config: WorkerConfig) -> bool:
    job = client.next_job()
    if job is None:
        return False

    job_id = str(job["job_id"])
    url = str(job["website_url"])
    business_name = str(job.get("business_name") or url)

    try:
        result = audit_url(url, timeout_seconds=config.job_timeout_seconds)
        result.update(capture_snapshots_and_redesign(url, business_name, result, timeout_seconds=20))
        result["lead_id"] = job["lead_id"]
        client.submit_result(job_id, result)
        print(f"Submitted audit result for job {job_id}.")
        return True
    except Exception as exc:
        client.submit_failure(job_id, str(exc), retryable=True)
        print(f"Audit failed for job {job_id}: {exc}", file=sys.stderr)
        return True


def _process_discovery_job(client: WorkerApiClient, config: WorkerConfig) -> bool:
    job = client.next_discovery_job()
    if job is None:
        return False

    job_id = str(job["job_id"])

    try:
        leads = discover_leads(job, provider=config.discovery_provider)
        client.submit_discovery_result(job_id, leads)
        print(f"Submitted {len(leads)} discovery leads for job {job_id}.")
        return True
    except Exception as exc:
        client.submit_discovery_failure(job_id, str(exc), retryable=True)
        print(f"Discovery failed for job {job_id}: {exc}", file=sys.stderr)
        return True
