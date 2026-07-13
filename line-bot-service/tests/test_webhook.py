import base64
import hashlib
import hmac
import json

from app.services.knowledge_api import KnowledgeSnapshot


def _sign_body(secret: str, body: str) -> str:
    digest = hmac.new(secret.encode(), body.encode(), hashlib.sha256).digest()
    return base64.b64encode(digest).decode()


def _base_event(reply_token: str) -> dict:
    return {
        "type": "message",
        "replyToken": reply_token,
        "webhookEventId": f"evt-{reply_token}",
        "deliveryContext": {"isRedelivery": False},
        "source": {"userId": "Utest", "type": "user"},
        "timestamp": 1_700_000_000_000,
        "mode": "active",
    }


def _text_event_payload(
    text: str = "สวัสดี",
    reply_token: str = "reply-token-text",
) -> dict:
    return {
        "events": [
            {
                **_base_event(reply_token),
                "message": {
                    "type": "text",
                    "id": "msg-text-1",
                    "text": text,
                    "quoteToken": "test-quote-token",
                },
            }
        ]
    }


def _sticker_event_payload() -> dict:
    return {
        "events": [
            {
                **_base_event("reply-token-sticker"),
                "message": {
                    "type": "sticker",
                    "id": "msg-sticker-1",
                    "stickerId": "1",
                    "packageId": "1",
                    "stickerResourceType": "STATIC",
                    "quoteToken": "test-quote-token",
                },
            }
        ]
    }


def test_health(client):
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json() == {"status": "ok"}


def test_webhook_invalid_signature_returns_400(client, settings):
    body = json.dumps(_text_event_payload())
    response = client.post(
        "/webhook",
        content=body,
        headers={
            "Content-Type": "application/json",
            "X-Line-Signature": "invalid-signature",
        },
    )
    assert response.status_code == 400
    assert response.json()["detail"] == "invalid signature"


def test_webhook_text_message_calls_ai_replies_and_records_analytics(
    client, settings, mock_ai_service, mock_line_client, mock_analytics_client
):
    body = json.dumps(_text_event_payload("ราคาตรวจสุขภาพเท่าไหร่"))
    signature = _sign_body(settings.LINE_CHANNEL_SECRET, body)

    response = client.post(
        "/webhook",
        content=body,
        headers={
            "Content-Type": "application/json",
            "X-Line-Signature": signature,
        },
    )

    assert response.status_code == 200
    assert response.json() == "OK"
    mock_ai_service.get_reply.assert_called_once_with("ราคาตรวจสุขภาพเท่าไหร่")
    mock_line_client.reply_text.assert_called_once_with(
        "reply-token-text", "คำตอบจาก AI"
    )
    mock_analytics_client.record_interaction.assert_called_once()
    analytics_payload = mock_analytics_client.record_interaction.call_args.kwargs
    assert analytics_payload["event_id"] == "evt-reply-token-text"
    assert analytics_payload["question"] == "ราคาตรวจสุขภาพเท่าไหร่"
    assert analytics_payload["answer"] == "คำตอบจาก AI"
    assert analytics_payload["response_type"] == "ai"


def test_webhook_non_text_message_uses_fallback(
    client, settings, mock_ai_service, mock_line_client, mock_analytics_client
):
    body = json.dumps(_sticker_event_payload())
    signature = _sign_body(settings.LINE_CHANNEL_SECRET, body)

    response = client.post(
        "/webhook",
        content=body,
        headers={
            "Content-Type": "application/json",
            "X-Line-Signature": signature,
        },
    )

    assert response.status_code == 200
    mock_ai_service.get_reply.assert_not_called()
    mock_line_client.reply_text.assert_called_once()
    _, reply_text = mock_line_client.reply_text.call_args[0]
    assert "ข้อความตัวอักษร" in reply_text
    assert mock_analytics_client.record_interaction.call_args.kwargs["response_type"] == "non_text"


def test_package_menu_replies_with_flex_carousel(
    client,
    settings,
    mock_ai_service,
    mock_line_client,
    mock_analytics_client,
    mock_knowledge_client,
):
    mock_knowledge_client.fetch_snapshot.return_value = KnowledgeSnapshot(
        packages=(
            {
                "code": "PKG-001",
                "category": "เลเซอร์และผิวพรรณ",
                "name_th": "เลเซอร์หน้าใส",
                "description_th": "ดูแลจุดด่างดำและรอยสิว",
                "price": 1500,
                "sale_price": 990,
            },
        ),
        faqs=(),
        entries=(),
    )
    body = json.dumps(
        _text_event_payload("ดูบริการและแพ็กเกจ", "reply-token-packages"),
        ensure_ascii=False,
    )
    signature = _sign_body(settings.LINE_CHANNEL_SECRET, body)

    response = client.post(
        "/webhook",
        content=body.encode("utf-8"),
        headers={"Content-Type": "application/json", "X-Line-Signature": signature},
    )

    assert response.status_code == 200
    mock_line_client.reply_message.assert_called_once()
    flex_message = mock_line_client.reply_message.call_args.args[1]
    assert flex_message.type == "flex"
    assert flex_message.contents.type == "carousel"
    assert len(flex_message.contents.contents) == 1
    mock_ai_service.get_reply.assert_not_called()
    assert mock_analytics_client.record_interaction.call_args.kwargs["response_type"] == "flex_carousel"


def test_promotion_menu_only_shows_sale_packages(
    client,
    settings,
    mock_line_client,
    mock_knowledge_client,
):
    mock_knowledge_client.fetch_snapshot.return_value = KnowledgeSnapshot(
        packages=(
            {"code": "REGULAR", "name_th": "ราคาปกติ", "price": 1000, "sale_price": None},
            {"code": "PROMO", "name_th": "ราคาโปร", "price": 1000, "sale_price": 790},
        ),
        faqs=(),
        entries=(),
    )
    body = json.dumps(
        _text_event_payload("ดูโปรโมชัน", "reply-token-promotions"),
        ensure_ascii=False,
    )
    signature = _sign_body(settings.LINE_CHANNEL_SECRET, body)

    response = client.post(
        "/webhook",
        content=body.encode("utf-8"),
        headers={"Content-Type": "application/json", "X-Line-Signature": signature},
    )

    assert response.status_code == 200
    flex_message = mock_line_client.reply_message.call_args.args[1]
    assert len(flex_message.contents.contents) == 1
    assert "ราคาโปร" in flex_message.contents.contents[0].body.contents[1].text
