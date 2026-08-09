from fastapi.testclient import TestClient

from ai_service.main import app, catalog_filters, event_data, nested


client = TestClient(app)


def test_health() -> None:
    response = client.get("/health")

    assert response.status_code == 200
    assert response.json()["status"] == "ok"


def test_chatwoot_webhook_fails_closed() -> None:
    response = client.post("/webhooks/chatwoot", json={"event": "message_created"})

    assert response.status_code == 401
    assert response.json() == {"detail": {"code": "invalid_webhook"}}


def test_catalog_filters_extract_only_allowlisted_values() -> None:
    filters = catalog_filters("คอนโด 2 ห้องนอน งบไม่เกิน 4 ล้านบาท แถวบางนา")

    assert filters["category_slug"] == "condo"
    assert filters["attributes"] == {"bedrooms": {"gte": 2}}
    assert filters["price"] == {"max": 4_000_000}
    assert filters["location"] == {"text": "บางนา"}


def test_event_data_requires_stable_message_identity() -> None:
    assert event_data({"account_id": 1, "conversation_id": 2, "content": "hello"}) is None
    assert event_data({"account_id": 1, "conversation_id": 2, "id": 3, "content": "hello", "message_type": "incoming"}) == (1, 2, 3, "hello", "incoming")


def test_nested_supports_list_indices_from_openrouter_response() -> None:
    payload = {"choices": [{"message": {"content": "grounded answer"}}]}

    assert nested(payload, "choices", 0, "message", "content") == "grounded answer"
