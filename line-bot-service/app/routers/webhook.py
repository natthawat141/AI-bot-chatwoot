import base64
import hashlib
import hmac
import json
import logging
from threading import Lock
from time import perf_counter

from fastapi import APIRouter, BackgroundTasks, HTTPException, Request
from linebot.v3 import WebhookHandler
from linebot.v3.webhooks import MessageEvent, TextMessageContent

from app.config import Settings
from app.services.ai import AIService, FALLBACK_REPLY
from app.services.analytics import AnalyticsClient
from app.services.knowledge import get_primary_location, is_location_query
from app.services.knowledge_api import KnowledgeApiClient
from app.services.line_client import LineClient
from app.services.package_carousel import (
    build_package_carousel,
    carousel_kind,
    select_packages,
)

logger = logging.getLogger("line_bot.webhook")

_event_lock = Lock()
_processing_event_ids: set[str] = set()
_completed_event_ids: set[str] = set()
_MAX_COMPLETED_EVENT_IDS = 10_000

NON_TEXT_REPLY = (
    "ขอโทษครับ ตอนนี้ผมตอบได้เฉพาะข้อความตัวอักษร พิมพ์คำถามมาได้เลยครับ"
)


def create_router(
    settings: Settings,
    ai_service: AIService,
    line_client: LineClient,
    analytics_client: AnalyticsClient,
    knowledge_client: KnowledgeApiClient,
) -> APIRouter:
    router = APIRouter()
    handler = WebhookHandler(settings.LINE_CHANNEL_SECRET)

    def has_valid_signature(body: bytes, signature: str) -> bool:
        digest = hmac.new(
            settings.LINE_CHANNEL_SECRET.encode("utf-8"),
            body,
            hashlib.sha256,
        ).digest()
        expected = base64.b64encode(digest).decode("ascii")
        return hmac.compare_digest(expected, signature)

    def event_ids_from(body: bytes) -> set[str]:
        try:
            payload = json.loads(body)
        except (TypeError, ValueError):
            return set()
        return {
            event_id
            for event in payload.get("events", [])
            if (event_id := event.get("webhookEventId"))
        }

    def claim_events(event_ids: set[str]) -> bool:
        if not event_ids:
            return True
        with _event_lock:
            if event_ids <= (_processing_event_ids | _completed_event_ids):
                return False
            _processing_event_ids.update(event_ids)
            return True

    def process_events(body: str, signature: str, event_ids: set[str]) -> None:
        try:
            handler.handle(body, signature)
        except Exception:
            with _event_lock:
                _processing_event_ids.difference_update(event_ids)
            logger.exception("Webhook background processing failed")
            return

        with _event_lock:
            _processing_event_ids.difference_update(event_ids)
            _completed_event_ids.update(event_ids)
            if len(_completed_event_ids) > _MAX_COMPLETED_EVENT_IDS:
                _completed_event_ids.clear()

    @handler.add(MessageEvent, message=TextMessageContent)
    def handle_text_message(event):
        started_at = perf_counter()
        event_id = getattr(event, "webhook_event_id", None) or f"message-{event.message.id}"
        user_id = getattr(getattr(event, "source", None), "user_id", None)

        if kind := carousel_kind(event.message.text):
            snapshot = knowledge_client.fetch_snapshot()
            packages = select_packages(snapshot.packages, kind) if snapshot else []
            if packages:
                answer = "ส่งรายการโปรโมชัน" if kind == "promotions" else "ส่งรายการบริการและแพ็กเกจ"
                sent = line_client.reply_message(
                    event.reply_token,
                    build_package_carousel(packages, kind),
                )
                response_type = "flex_carousel"
            else:
                answer = (
                    "ขณะนี้ยังไม่มีโปรโมชันที่เปิดใช้งาน กรุณาสอบถามเจ้าหน้าที่ครับ"
                    if kind == "promotions"
                    else "ขณะนี้ยังไม่มีแพ็กเกจที่เปิดใช้งาน กรุณาสอบถามเจ้าหน้าที่ครับ"
                )
                sent = line_client.reply_text(event.reply_token, answer)
                response_type = "flex_empty"
            analytics_client.record_interaction(
                event_id=event_id,
                message_id=event.message.id,
                user_id=user_id,
                question=event.message.text,
                answer=answer,
                response_type=response_type,
                status="answered" if sent is not False else "failed",
                model=None,
                duration_ms=int((perf_counter() - started_at) * 1000),
            )
            return

        if is_location_query(event.message.text):
            location = get_primary_location()
            answer = f"ส่งพิกัด {location.title}: {location.address}"
            sent = line_client.reply_location(
                event.reply_token,
                title=location.title,
                address=location.address,
                latitude=location.latitude,
                longitude=location.longitude,
            )
            analytics_client.record_interaction(
                event_id=event_id,
                message_id=event.message.id,
                user_id=user_id,
                question=event.message.text,
                answer=answer,
                response_type="location",
                status="answered" if sent is not False else "failed",
                model=None,
                duration_ms=int((perf_counter() - started_at) * 1000),
            )
            return

        answer = ai_service.get_reply(event.message.text)
        sent = line_client.reply_text(event.reply_token, answer)
        analytics_client.record_interaction(
            event_id=event_id,
            message_id=event.message.id,
            user_id=user_id,
            question=event.message.text,
            answer=answer,
            response_type="fallback" if answer == FALLBACK_REPLY else "ai",
            status="answered" if sent is not False else "failed",
            model=settings.OPENROUTER_MODEL,
            duration_ms=int((perf_counter() - started_at) * 1000),
        )

    @handler.add(MessageEvent)
    def handle_non_text_message(event):
        started_at = perf_counter()
        sent = line_client.reply_text(event.reply_token, NON_TEXT_REPLY)
        message_type = getattr(event.message, "type", "non_text")
        message_id = getattr(event.message, "id", None)
        event_id = getattr(event, "webhook_event_id", None) or f"message-{message_id or 'unknown'}"
        user_id = getattr(getattr(event, "source", None), "user_id", None)
        analytics_client.record_interaction(
            event_id=event_id,
            message_id=message_id,
            user_id=user_id,
            question=f"[{message_type}]",
            answer=NON_TEXT_REPLY,
            response_type="non_text",
            status="answered" if sent is not False else "failed",
            model=None,
            duration_ms=int((perf_counter() - started_at) * 1000),
        )

    @router.post("/webhook")
    async def webhook(request: Request, background_tasks: BackgroundTasks):
        signature = request.headers.get("X-Line-Signature", "")
        body_bytes = await request.body()
        if not has_valid_signature(body_bytes, signature):
            raise HTTPException(status_code=400, detail="invalid signature") from None

        body = body_bytes.decode("utf-8")
        event_ids = event_ids_from(body_bytes)
        if claim_events(event_ids):
            background_tasks.add_task(process_events, body, signature, event_ids)
        return "OK"

    return router
