import base64
import hashlib
import hmac
import json

import pytest

from app.services.knowledge import get_primary_location, is_location_query


@pytest.mark.parametrize(
    "query",
    ["คลินิกอยู่ไหน", "ขอพิกัด", "เปิดแผนที่", "clinic location", "ADDRESS"],
)
def test_location_intent(query):
    assert is_location_query(query)


def test_non_location_intent():
    assert not is_location_query("มีบริการอะไรบ้าง")


def test_primary_location_is_verified_w_medic_branch():
    location = get_primary_location()
    assert location.title == "W+ Medic Clinic - Bang Yai"
    assert location.latitude == 13.8752532
    assert location.longitude == 100.4223009


def test_webhook_location_question_replies_with_location(
    client, settings, mock_ai_service, mock_line_client, mock_analytics_client
):
    payload = {
        "events": [
            {
                "type": "message",
                "replyToken": "reply-token-location",
                "webhookEventId": "evt-location",
                "deliveryContext": {"isRedelivery": False},
                "source": {"userId": "Utest", "type": "user"},
                "timestamp": 1_700_000_000_000,
                "mode": "active",
                "message": {
                    "type": "text",
                    "id": "msg-location",
                    "text": "คลินิกอยู่ไหน",
                    "quoteToken": "test-quote-token",
                },
            }
        ]
    }
    body = json.dumps(payload)
    digest = hmac.new(
        settings.LINE_CHANNEL_SECRET.encode(), body.encode(), hashlib.sha256
    ).digest()
    signature = base64.b64encode(digest).decode()

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
    mock_line_client.reply_text.assert_not_called()
    mock_line_client.reply_location.assert_called_once_with(
        "reply-token-location",
        title="W+ Medic Clinic - Bang Yai",
        address=(
            "56/19-20 Moo 15, Rattanathibet Road, Bang Rak Phatthana, "
            "Bang Bua Thong, Nonthaburi 11110"
        ),
        latitude=13.8752532,
        longitude=100.4223009,
    )
    analytics_payload = mock_analytics_client.record_interaction.call_args.kwargs
    assert analytics_payload["question"] == "คลินิกอยู่ไหน"
    assert analytics_payload["response_type"] == "location"
