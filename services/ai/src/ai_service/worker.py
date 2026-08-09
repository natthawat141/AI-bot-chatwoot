"""Durable Redis worker for Chatwoot webhook events."""

from __future__ import annotations

import asyncio
import json
import logging
from collections.abc import Mapping

import httpx
from redis import asyncio as redis_async

from ai_service.main import DEAD_LETTER_QUEUE_KEY, QUEUE_KEY, Settings, process

LOG = logging.getLogger("ai_worker")
MAX_RETRIES = 3


async def run() -> None:
    settings = Settings.from_env()
    if not settings.redis_url:
        raise RuntimeError("REDIS_URL or AI_QUEUE_REDIS_URL is required")

    queue = redis_async.from_url(settings.redis_url, decode_responses=True)
    try:
        async with httpx.AsyncClient() as client:
            while True:
                item = await queue.brpop(QUEUE_KEY, timeout=5)
                if item is None:
                    continue
                _, raw = item
                payload: object = None
                try:
                    payload = json.loads(raw)
                    if not isinstance(payload, Mapping):
                        raise ValueError("payload must be an object")
                    attempts = int(payload.get("_ai_attempts", 0))
                    event = {key: value for key, value in payload.items() if key != "_ai_attempts"}
                    await process(settings, event, client)
                except Exception as exc:  # Keep one malformed event from stopping the worker.
                    attempts = int(payload.get("_ai_attempts", 0)) if isinstance(payload, Mapping) else 0
                    retry_payload = dict(payload) if isinstance(payload, Mapping) else {"payload": raw}
                    if attempts < MAX_RETRIES:
                        retry_payload["_ai_attempts"] = attempts + 1
                        await queue.rpush(QUEUE_KEY, json.dumps(retry_payload, ensure_ascii=False, separators=(",", ":")))
                        LOG.warning("event_retry attempt=%d error=%s", attempts + 1, type(exc).__name__)
                    else:
                        retry_payload["_ai_attempts"] = attempts
                        await queue.lpush(DEAD_LETTER_QUEUE_KEY, json.dumps(retry_payload, ensure_ascii=False, separators=(",", ":")))
                        await queue.ltrim(DEAD_LETTER_QUEUE_KEY, 0, 999)
                        LOG.error("event_dead_letter attempts=%d error=%s", attempts, type(exc).__name__)
    finally:
        await queue.aclose()


if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO)
    asyncio.run(run())
