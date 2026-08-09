"""Fail-closed Chatwoot conversation orchestration. Never logs customer content."""

from __future__ import annotations

import asyncio
import base64
import hmac
import json
import logging
import os
import re
import time
from collections import OrderedDict
from collections.abc import Mapping
from contextlib import asynccontextmanager
from dataclasses import dataclass
from typing import Any

import httpx
from fastapi import FastAPI, HTTPException, Request, status
from redis import asyncio as redis_async
from redis.exceptions import RedisError

LOG = logging.getLogger("ai_service")
HANDOFF_TERMS = ("เจ้าหน้าที่", "พนักงาน", "คน", "human", "agent", "ร้องเรียน", "ต่อรอง", "ชำระ", "จ่ายเงิน", "refund")
CATALOG_TERMS = ("คอนโด", "ที่ดิน", "บ้าน", "อสังหา", "เช่า", "ขาย", "ราคา", "แพ็กเกจ", "สินค้า", "บริการ")
QUEUE_KEY = "aibot:chatwoot:webhooks:v1"
DEAD_LETTER_QUEUE_KEY = "aibot:chatwoot:webhooks:dead:v1"
MAX_CONVERSATION_LOCKS = 10_000
CONVERSATION_LOCKS: OrderedDict[str, asyncio.Lock] = OrderedDict()


@dataclass(frozen=True)
class Settings:
    management_base_url: str
    management_token: str
    chatwoot_base_url: str
    chatwoot_bot_token: str
    chatwoot_account_id: int
    chatwoot_team_id: int
    webhook_token: str
    openrouter_api_key: str
    openrouter_model: str
    allowed_inbox_ids: frozenset[int]
    redis_url: str

    @classmethod
    def from_env(cls) -> "Settings":
        def integer(name: str, default: int = 0) -> int:
            try:
                return int(os.getenv(name, str(default)))
            except ValueError:
                return default

        inboxes: set[int] = set()
        for value in os.getenv("CHATWOOT_ALLOWED_INBOX_IDS", "").split(","):
            if value.strip().isdigit():
                inboxes.add(int(value.strip()))
        return cls(
            management_base_url=os.getenv("MANAGEMENT_BASE_URL", "http://management-nginx").rstrip("/"),
            management_token=os.getenv("AI_SERVICE_TOKEN", ""),
            chatwoot_base_url=os.getenv("CHATWOOT_BASE_URL", "http://chatwoot-rails:3000").rstrip("/"),
            chatwoot_bot_token=os.getenv("CHATWOOT_API_TOKEN", os.getenv("CHATWOOT_BOT_ACCESS_TOKEN", "")),
            chatwoot_account_id=integer("CHATWOOT_ACCOUNT_ID"),
            chatwoot_team_id=integer("CHATWOOT_HANDOFF_TEAM_ID"),
            webhook_token=os.getenv("CHATWOOT_WEBHOOK_TOKEN", ""),
            openrouter_api_key=os.getenv("OPENROUTER_API_KEY", ""),
            openrouter_model=os.getenv("OPENROUTER_MODEL", "deepseek/deepseek-v4-flash-0731"),
            allowed_inbox_ids=frozenset(inboxes),
            redis_url=os.getenv("AI_QUEUE_REDIS_URL", os.getenv("REDIS_URL", "")).strip(),
        )


class UpstreamError(RuntimeError):
    pass


class ChatwootClient:
    def __init__(self, settings: Settings, client: httpx.AsyncClient) -> None:
        self.settings, self.client = settings, client

    @property
    def headers(self) -> dict[str, str]:
        return {"api_access_token": self.settings.chatwoot_bot_token, "Content-Type": "application/json"}

    async def _request(self, method: str, path: str, **kwargs: Any) -> dict[str, Any]:
        try:
            response = await self.client.request(method, self.settings.chatwoot_base_url + path, headers=self.headers, timeout=8, **kwargs)
            response.raise_for_status()
            data = response.json()
            return data if isinstance(data, dict) else {}
        except (httpx.HTTPError, ValueError) as exc:
            raise UpstreamError("chatwoot") from exc

    async def conversation(self, account_id: int, conversation_id: int) -> dict[str, Any]:
        return await self._request("GET", f"/api/v1/accounts/{account_id}/conversations/{conversation_id}")

    async def custom_attributes(self, account_id: int, conversation_id: int, attributes: Mapping[str, Any]) -> None:
        await self._request("POST", f"/api/v1/accounts/{account_id}/conversations/{conversation_id}/custom_attributes", json={"custom_attributes": dict(attributes)})

    async def assign_team(self, account_id: int, conversation_id: int, team_id: int) -> None:
        await self._request("POST", f"/api/v1/accounts/{account_id}/conversations/{conversation_id}/assignments", json={"team_id": team_id})

    async def set_open(self, account_id: int, conversation_id: int) -> None:
        await self._request("PATCH", f"/api/v1/accounts/{account_id}/conversations/{conversation_id}", json={"status": "open"})

    async def message(self, account_id: int, conversation_id: int, content: str, private: bool = False) -> None:
        await self._request("POST", f"/api/v1/accounts/{account_id}/conversations/{conversation_id}/messages", json={"content": content, "message_type": "outgoing", "private": private})


class ManagementClient:
    def __init__(self, settings: Settings, client: httpx.AsyncClient) -> None:
        self.settings, self.client = settings, client

    @property
    def headers(self) -> dict[str, str]:
        return {"Authorization": f"Bearer {self.settings.management_token}", "Accept": "application/json"}

    async def _request(self, method: str, path: str, **kwargs: Any) -> list[dict[str, Any]]:
        try:
            response = await self.client.request(method, self.settings.management_base_url + path, headers=self.headers, timeout=5, **kwargs)
            response.raise_for_status()
            payload = response.json()
            data = payload.get("data", []) if isinstance(payload, dict) else []
            if not isinstance(data, list):
                raise ValueError("unexpected response schema")
            return [item for item in data if isinstance(item, dict)][:20]
        except (httpx.HTTPError, ValueError) as exc:
            raise UpstreamError("management") from exc

    async def search(self, filters: dict[str, Any]) -> list[dict[str, Any]]:
        return await self._request("POST", "/api/v1/catalog/search", json=filters)

    async def knowledge(self) -> list[dict[str, Any]]:
        faqs, knowledge = await asyncio.gather(self._request("GET", "/api/v1/faqs?limit=10"), self._request("GET", "/api/v1/knowledge?limit=10"))
        return (faqs + knowledge)[:20]


def nested(payload: Mapping[str, Any], *keys: str | int) -> Any:
    value: Any = payload
    for key in keys:
        if isinstance(value, Mapping):
            value = value.get(key)
        elif isinstance(value, list) and isinstance(key, int) and 0 <= key < len(value):
            value = value[key]
        else:
            return None
    return value


def event_data(payload: Mapping[str, Any]) -> tuple[int, int, int, str, str] | None:
    message: Mapping[str, Any] = payload.get("message") if isinstance(payload.get("message"), Mapping) else payload
    account_id = nested(payload, "account", "id") or payload.get("account_id") or nested(message, "account", "id")
    conversation_id = nested(message, "conversation", "id") or payload.get("conversation_id") or nested(payload, "conversation", "id")
    message_id = message.get("id") or payload.get("message_id")
    content = message.get("content") or payload.get("content")
    message_type = message.get("message_type") or payload.get("message_type")
    if not all(isinstance(value, int) for value in (account_id, conversation_id, message_id)) or not isinstance(content, str):
        return None
    return account_id, conversation_id, message_id, content[:4000], str(message_type or "")


def is_handoff(message: str) -> bool:
    lower = message.lower()
    return any(term in lower for term in HANDOFF_TERMS)


def is_catalog(message: str) -> bool:
    lower = message.lower()
    return any(term in lower for term in CATALOG_TERMS)


def catalog_filters(message: str) -> dict[str, Any]:
    lower = message.lower()
    filters: dict[str, Any] = {"limit": 10, "sort": "relevance"}
    if "คอนโด" in lower:
        filters["category_slug"] = "condo"
    elif "ที่ดิน" in lower:
        filters["category_slug"] = "land"
    elif "บ้าน" in lower:
        filters["category_slug"] = "house"
    if "เช่า" in lower:
        filters["transaction_type"] = "rent"
    elif "ขาย" in lower or "ซื้อ" in lower:
        filters["transaction_type"] = "sale"
    if match := re.search(r"(\d+)\s*ห้องนอน", lower):
        filters["attributes"] = {"bedrooms": {"gte": int(match.group(1))}}
    if match := re.search(r"(?:ไม่เกิน|งบ)\s*([0-9]+(?:\.[0-9]+)?)\s*(ล้าน|แสน|บาท)?", lower):
        value, unit = float(match.group(1)), match.group(2) or "บาท"
        multiplier = {"ล้าน": 1_000_000, "แสน": 100_000, "บาท": 1}[unit]
        filters["price"] = {"max": value * multiplier}
    if match := re.search(r"(?:แถว|ย่าน|ที่)\s*([ก-๙A-Za-z0-9-]{2,80})", message):
        filters["location"] = {"text": match.group(1)}
    return filters


def is_ai_eligible(conversation: Mapping[str, Any], settings: Settings) -> bool:
    status_value = str(conversation.get("status", ""))
    attrs = conversation.get("custom_attributes") or {}
    if not isinstance(attrs, Mapping) or attrs.get("ai_mode", "ai") != "ai" or status_value in {"resolved", "snoozed"}:
        return False
    inbox = conversation.get("inbox_id") or nested(conversation, "inbox", "id")
    if settings.allowed_inbox_ids and inbox not in settings.allowed_inbox_ids:
        return False
    assignee = conversation.get("assignee") or nested(conversation, "meta", "assignee")
    return not isinstance(assignee, Mapping) and not isinstance(conversation.get("assignee_id"), int)


def compact_records(records: list[dict[str, Any]]) -> str:
    return json.dumps(records, ensure_ascii=False, separators=(",", ":"))[:12000]


def conversation_lock(key: str) -> asyncio.Lock:
    """Return a bounded per-conversation lock map for this worker process."""
    lock = CONVERSATION_LOCKS.get(key)
    if lock is None:
        lock = asyncio.Lock()
        CONVERSATION_LOCKS[key] = lock
    CONVERSATION_LOCKS.move_to_end(key)
    if len(CONVERSATION_LOCKS) > MAX_CONVERSATION_LOCKS:
        for old_key, old_lock in list(CONVERSATION_LOCKS.items()):
            if not old_lock.locked() and old_key != key:
                CONVERSATION_LOCKS.pop(old_key, None)
                break
    return lock


async def enqueue_webhook(queue: Any, payload: Mapping[str, Any]) -> None:
    if queue is None:
        raise UpstreamError("queue_not_configured")
    await queue.rpush(QUEUE_KEY, json.dumps(dict(payload), ensure_ascii=False, separators=(",", ":")))


async def grounded_answer(settings: Settings, client: httpx.AsyncClient, question: str, records: list[dict[str, Any]]) -> str | None:
    if not settings.openrouter_api_key:
        return None
    prompt = (
        "คุณคือผู้ช่วยธุรกิจ ตอบภาษาไทยอย่างกระชับ ใช้ข้อเท็จจริงจาก CONTEXT เท่านั้น "
        "ห้ามแต่งข้อมูลราคา ความพร้อม หรือรายการ หากไม่มีข้อมูลให้บอกว่าตรวจสอบไม่ได้และเสนอส่งต่อเจ้าหน้าที่. "
        f"CONTEXT={compact_records(records)}\nQUESTION={question[:2000]}"
    )
    try:
        response = await client.post("https://openrouter.ai/api/v1/chat/completions", headers={"Authorization": f"Bearer {settings.openrouter_api_key}", "Content-Type": "application/json"}, json={"model": settings.openrouter_model, "messages": [{"role": "system", "content": "Follow the supplied data only."}, {"role": "user", "content": prompt}], "temperature": 0.2, "max_tokens": 500}, timeout=15)
        response.raise_for_status()
        content = nested(response.json(), "choices", 0, "message", "content")
        return content.strip()[:3000] if isinstance(content, str) and content.strip() else None
    except (httpx.HTTPError, ValueError, TypeError):
        return None


async def handoff(chatwoot: ChatwootClient, account_id: int, conversation_id: int, reason: str) -> None:
    if not chatwoot.settings.chatwoot_team_id:
        raise UpstreamError("handoff_team_not_configured")
    await chatwoot.custom_attributes(account_id, conversation_id, {"ai_mode": "human", "ai_handoff_reason": reason})
    await chatwoot.set_open(account_id, conversation_id)
    await chatwoot.assign_team(account_id, conversation_id, chatwoot.settings.chatwoot_team_id)
    await chatwoot.message(account_id, conversation_id, "รับเรื่องแล้วครับ กำลังส่งต่อให้ทีมเจ้าหน้าที่ดูแลต่อให้")
    await chatwoot.message(account_id, conversation_id, f"AI handoff: {reason}", private=True)


async def process(settings: Settings, payload: Mapping[str, Any], client: httpx.AsyncClient) -> None:
    event = event_data(payload)
    if event is None:
        LOG.info("ignored_event reason=shape")
        return
    account_id, conversation_id, message_id, content, message_type = event
    if settings.chatwoot_account_id and account_id != settings.chatwoot_account_id:
        LOG.info("ignored_event account=%s conversation=%s reason=account", account_id, conversation_id)
        return
    if message_type not in {"incoming", "0", ""}:
        LOG.info("ignored_event account=%s conversation=%s reason=direction", account_id, conversation_id)
        return
    lock = conversation_lock(f"{account_id}:{conversation_id}")
    async with lock:
        started = time.monotonic()
        chatwoot, management = ChatwootClient(settings, client), ManagementClient(settings, client)
        try:
            conversation = await chatwoot.conversation(account_id, conversation_id)
            if not is_ai_eligible(conversation, settings):
                LOG.info("ignored_event account=%s conversation=%s reason=ownership", account_id, conversation_id)
                return
            attrs = conversation.get("custom_attributes") or {}
            if isinstance(attrs, Mapping) and str(attrs.get("ai_last_message_id", "")) == str(message_id):
                LOG.info("ignored_event account=%s conversation=%s reason=duplicate", account_id, conversation_id)
                return
            if is_handoff(content):
                await handoff(chatwoot, account_id, conversation_id, "customer_request")
                LOG.info("handoff account=%s conversation=%s duration_ms=%d", account_id, conversation_id, int((time.monotonic() - started) * 1000))
                return
            records = await management.search(catalog_filters(content)) if is_catalog(content) else await management.knowledge()
            if is_catalog(content) and not records:
                answer = "ตอนนี้ยังไม่พบรายการที่ตรงตามเงื่อนไขครับ ต้องการให้เจ้าหน้าที่ช่วยค้นหาตัวเลือกใกล้เคียงให้ไหม"
            else:
                answer = await grounded_answer(settings, client, content, records)
            if not answer:
                await handoff(chatwoot, account_id, conversation_id, "cannot_confirm")
                LOG.info("handoff account=%s conversation=%s reason=cannot_confirm", account_id, conversation_id)
                return
            latest = await chatwoot.conversation(account_id, conversation_id)
            if not is_ai_eligible(latest, settings):
                LOG.info("ignored_event account=%s conversation=%s reason=ownership_race", account_id, conversation_id)
                return
            await chatwoot.custom_attributes(account_id, conversation_id, {"ai_last_message_id": str(message_id), "ai_mode": "ai"})
            await chatwoot.message(account_id, conversation_id, answer)
            LOG.info("answer account=%s conversation=%s action=%s duration_ms=%d", account_id, conversation_id, "catalog" if is_catalog(content) else "knowledge", int((time.monotonic() - started) * 1000))
        except UpstreamError as exc:
            LOG.warning("retryable_failure account=%s conversation=%s upstream=%s", account_id, conversation_id, str(exc))
            raise


@asynccontextmanager
async def lifespan(app: FastAPI):
    app.state.http = httpx.AsyncClient()
    queue_url = Settings.from_env().redis_url
    app.state.queue = redis_async.from_url(queue_url, decode_responses=True) if queue_url else None
    try:
        yield
    finally:
        await app.state.http.aclose()
        if app.state.queue is not None:
            await app.state.queue.aclose()


app = FastAPI(title="AI Bot Chatwoot Service", version="1.0.0", docs_url=None, redoc_url=None, lifespan=lifespan)


@app.get("/health")
async def health() -> dict[str, str]:
    configured = Settings.from_env()
    queue_ready = bool(configured.redis_url)
    queue = getattr(app.state, "queue", None)
    if queue is not None:
        try:
            await queue.ping()
        except RedisError:
            queue_ready = False
    ready = configured.webhook_token and configured.management_token and configured.chatwoot_bot_token and queue_ready
    return {"status": "ok", "mode": "ready" if ready else "configuration_required"}


async def receive_chatwoot_webhook(request: Request, supplied_path_token: str = "") -> dict[str, str]:
    settings = Settings.from_env()
    supplied = request.headers.get("x-chatwoot-webhook-token", "") or supplied_path_token
    if not settings.webhook_token or not hmac.compare_digest(supplied, settings.webhook_token):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail={"code": "invalid_webhook"})
    try:
        payload = await request.json()
    except json.JSONDecodeError as exc:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail={"code": "invalid_json"}) from exc
    if not isinstance(payload, Mapping):
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail={"code": "invalid_payload"})
    if not settings.management_token or not settings.chatwoot_bot_token:
        raise HTTPException(status_code=status.HTTP_503_SERVICE_UNAVAILABLE, detail={"code": "not_configured"})
    try:
        await enqueue_webhook(request.app.state.queue, payload)
    except (RedisError, UpstreamError) as exc:
        LOG.error("queue_failure error=%s", type(exc).__name__)
        raise HTTPException(status_code=status.HTTP_503_SERVICE_UNAVAILABLE, detail={"code": "queue_unavailable"}) from exc
    return {"status": "accepted"}


@app.post("/webhooks/chatwoot", status_code=status.HTTP_202_ACCEPTED, include_in_schema=False)
async def chatwoot_webhook_legacy(request: Request) -> dict[str, str]:
    return await receive_chatwoot_webhook(request)


@app.post("/webhooks/chatwoot/{path_token}", status_code=status.HTTP_202_ACCEPTED, include_in_schema=False)
async def chatwoot_webhook(request: Request, path_token: str) -> dict[str, str]:
    return await receive_chatwoot_webhook(request, path_token)
