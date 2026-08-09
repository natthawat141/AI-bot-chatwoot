from fastapi.testclient import TestClient

from ai_service.main import Settings, app, catalog_filters, event_data, is_ai_eligible, nested


client = TestClient(app)


def test_health() -> None:
    response = client.get("/health")

    assert response.status_code == 200
    assert response.json()["status"] == "ok"


def test_chatwoot_webhook_fails_closed() -> None:
    response = client.post("/webhooks/chatwoot", json={"event": "message_created"})

    assert response.status_code == 401
    assert response.json() == {"detail": {"code": "invalid_webhook"}}


def test_chatwoot_webhook_enqueues_authenticated_event(monkeypatch) -> None:
    class FakeQueue:
        def __init__(self) -> None:
            self.items: list[tuple[str, str]] = []

        async def rpush(self, key: str, value: str) -> None:
            self.items.append((key, value))

        async def aclose(self) -> None:
            return None

    monkeypatch.setenv("CHATWOOT_WEBHOOK_TOKEN", "test-secret")
    monkeypatch.setenv("AI_SERVICE_TOKEN", "management-token")
    monkeypatch.setenv("CHATWOOT_API_TOKEN", "chatwoot-token")
    with TestClient(app) as test_client:
        queue = FakeQueue()
        app.state.queue = queue
        response = test_client.post("/webhooks/chatwoot/test-secret", json={"event": "message_created"})

    assert response.status_code == 202
    assert response.json() == {"status": "accepted"}
    assert len(queue.items) == 1


def test_chatwoot_webhook_rejects_invalid_path_token(monkeypatch) -> None:
    monkeypatch.setenv("CHATWOOT_WEBHOOK_TOKEN", "test-secret")
    response = client.post("/webhooks/chatwoot/wrong-secret", json={"event": "message_created"})

    assert response.status_code == 401


def test_chatwoot_webhook_does_not_accept_query_string_tokens(monkeypatch) -> None:
    monkeypatch.setenv("CHATWOOT_WEBHOOK_TOKEN", "test-secret")
    response = client.post("/webhooks/chatwoot?token=test-secret", json={"event": "message_created"})

    assert response.status_code == 401


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


def test_ai_assigned_agent_bot_remains_eligible() -> None:
    settings = Settings.from_env()

    assert is_ai_eligible(
        {
            "status": "open",
            "inbox_id": 1,
            "meta": {"assignee": {"id": 1, "bot_type": "webhook"}},
            "custom_attributes": {},
        },
        settings,
    )


def test_human_assignee_is_not_ai_eligible() -> None:
    settings = Settings.from_env()

    assert not is_ai_eligible(
        {
            "status": "open",
            "inbox_id": 1,
            "meta": {"assignee": {"id": 2, "name": "Human Agent"}},
            "custom_attributes": {},
        },
        settings,
    )
