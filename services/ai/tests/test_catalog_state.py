import asyncio
import json

import httpx

from ai_service.main import (
    apply_resets,
    detect_intent,
    merge_catalog_filters,
    requested_result_index,
    search_query,
    ManagementClient,
    Settings,
    process,
)


def test_merge_preserves_previous_filters() -> None:
    previous = {
        "category_slug": "condo",
        "location": {"text": "บางนา"},
        "price": {"max": 4_000_000},
    }
    result = merge_catalog_filters(previous, {"attributes": {"bedrooms": {"gte": 2}}})

    assert result["location"]["text"] == "บางนา"
    assert result["price"]["max"] == 4_000_000
    assert result["attributes"]["bedrooms"]["gte"] == 2


def test_transaction_type_overwrite_keeps_rest() -> None:
    result = merge_catalog_filters(
        {"category_slug": "condo", "location": {"text": "บางนา"}, "transaction_type": "sale"},
        {"transaction_type": "rent"},
    )

    assert result["transaction_type"] == "rent"
    assert result["location"]["text"] == "บางนา"


def test_reset_and_followup_routing() -> None:
    filters = apply_resets("ไม่จำกัดงบ", {"price": {"max": 4_000_000}, "location": {"text": "บางนา"}})

    assert "price" not in filters
    assert filters["location"]["text"] == "บางนา"
    assert detect_intent("เอา 2 ห้องนอน", "catalog") == "catalog"


def test_search_query_removes_question_fillers() -> None:
    assert search_query("ชำระเงินยังไงครับ") == "ชำระเงิน"


def test_management_knowledge_passes_bounded_search_query() -> None:
    seen: list[dict[str, str]] = []

    def transport(request: httpx.Request) -> httpx.Response:
        if request.method == "GET":
            seen.append(dict(request.url.params))
        return httpx.Response(200, json={"data": [{"title": "การชำระเงิน"}]})

    async def scenario() -> list[dict[str, object]]:
        settings = Settings(
            management_base_url="http://management",
            management_token="management-token",
            chatwoot_base_url="http://chatwoot",
            chatwoot_bot_token="chatwoot-token",
            chatwoot_account_id=1,
            chatwoot_team_id=2,
            webhook_token="webhook-token",
            openrouter_api_key="openrouter-key",
            openrouter_model="test-model",
            allowed_inbox_ids=frozenset(),
            redis_url="redis://redis",
        )
        async with httpx.AsyncClient(transport=httpx.MockTransport(transport)) as client:
            return await ManagementClient(settings, client).knowledge(search_query("ชำระเงินยังไงครับ"))

    records = asyncio.run(scenario())

    assert records == [{"title": "การชำระเงิน"}, {"title": "การชำระเงิน"}]
    assert seen == [
        {"limit": "5", "q": "ชำระเงิน"},
        {"limit": "5", "q": "ชำระเงิน"},
    ]


def test_management_knowledge_does_not_fallback_to_unrelated_rows() -> None:
    seen: list[dict[str, str]] = []

    def transport(request: httpx.Request) -> httpx.Response:
        seen.append(dict(request.url.params))
        if "q" in request.url.params:
            return httpx.Response(200, json={"data": []})
        return httpx.Response(200, json={"data": [{"title": "ไม่เกี่ยวกับคำถาม"}]})

    async def scenario() -> list[dict[str, object]]:
        settings = Settings(
            management_base_url="http://management",
            management_token="management-token",
            chatwoot_base_url="http://chatwoot",
            chatwoot_bot_token="chatwoot-token",
            chatwoot_account_id=1,
            chatwoot_team_id=2,
            webhook_token="webhook-token",
            openrouter_api_key="openrouter-key",
            openrouter_model="test-model",
            allowed_inbox_ids=frozenset(),
            redis_url="redis://redis",
        )
        async with httpx.AsyncClient(transport=httpx.MockTransport(transport)) as client:
            return await ManagementClient(settings, client).knowledge("คำค้นที่ไม่มีอยู่จริง")

    records = asyncio.run(scenario())

    assert records == []
    assert seen == [
        {"limit": "5", "q": "คำค้นที่ไม่มีอยู่จริง"},
        {"limit": "5", "q": "คำค้นที่ไม่มีอยู่จริง"},
    ]


def test_process_handoffs_when_knowledge_context_is_empty() -> None:
    class EmptyKnowledgeTransport:
        def __init__(self) -> None:
            self.attributes: dict[str, object] = {}
            self.public_messages = 0

        def __call__(self, request: httpx.Request) -> httpx.Response:
            path = request.url.path
            if request.url.host == "openrouter.ai":
                return httpx.Response(200, json={"choices": [{"message": {"content": "ข้อมูลที่ไม่ควรแต่งขึ้น"}}]})
            if path.endswith("/conversations/7") and request.method == "GET":
                return httpx.Response(200, json={
                    "status": "open",
                    "inbox_id": 1,
                    "meta": {"assignee": {"bot_type": "webhook"}},
                    "custom_attributes": self.attributes,
                })
            if path.endswith("/messages") and request.method == "GET":
                return httpx.Response(200, json={"payload": []})
            if path.endswith("/custom_attributes") and request.method == "POST":
                payload = json.loads(request.content)
                self.attributes.update(payload["custom_attributes"])
                return httpx.Response(200, json={})
            if path.endswith("/messages") and request.method == "POST":
                if json.loads(request.content).get("private") is not True:
                    self.public_messages += 1
                return httpx.Response(200, json={})
            if request.method == "GET" and path.endswith(("/faqs", "/knowledge")):
                return httpx.Response(200, json={"data": []})
            return httpx.Response(200, json={"data": []})

    async def scenario() -> EmptyKnowledgeTransport:
        transport = EmptyKnowledgeTransport()
        settings = Settings(
            management_base_url="http://management",
            management_token="management-token",
            chatwoot_base_url="http://chatwoot",
            chatwoot_bot_token="chatwoot-token",
            chatwoot_account_id=1,
            chatwoot_team_id=2,
            webhook_token="webhook-token",
            openrouter_api_key="openrouter-key",
            openrouter_model="test-model",
            allowed_inbox_ids=frozenset(),
            redis_url="redis://redis",
        )
        async with httpx.AsyncClient(transport=httpx.MockTransport(transport)) as client:
            await process(settings, {
                "account": {"id": 1},
                "message": {
                    "id": 1,
                    "content": "นโยบายที่ไม่มีข้อมูล",
                    "message_type": "incoming",
                    "conversation": {"id": 7},
                },
            }, client)
        return transport

    transport = asyncio.run(scenario())

    assert transport.attributes["ai_mode"] == "human"
    assert transport.attributes["ai_handoff_reason"] == "cannot_confirm"
    assert transport.public_messages == 1


def test_ordinal_reference() -> None:
    assert requested_result_index("ตัวแรกกี่ตารางเมตร") == 0
    assert requested_result_index("ตัวที่สองราคาเท่าไหร่") == 1
    assert requested_result_index("มีคอนโดบางนาไหม") is None


class _ConversationFlowTransport:
    def __init__(self) -> None:
        self.attributes: dict[str, object] = {}
        self.search_payloads: list[dict[str, object]] = []
        self.detail_ids: list[int] = []

    def __call__(self, request: httpx.Request) -> httpx.Response:
        path = request.url.path
        if request.url.host == "openrouter.ai":
            return httpx.Response(200, json={"choices": [{"message": {"content": "ข้อมูลตรงตามรายการครับ"}}]})
        if path.endswith("/conversations/7") and request.method == "GET":
            return httpx.Response(200, json={
                "status": "open",
                "inbox_id": 1,
                "meta": {"assignee": {"bot_type": "webhook"}},
                "custom_attributes": self.attributes,
            })
        if path.endswith("/messages") and request.method == "GET":
            return httpx.Response(200, json={"payload": []})
        if path.endswith("/custom_attributes") and request.method == "POST":
            payload = json.loads(request.content)
            self.attributes.update(payload["custom_attributes"])
            return httpx.Response(200, json={})
        if path.endswith("/messages") and request.method == "POST":
            return httpx.Response(200, json={})
        if path.endswith("/catalog/search") and request.method == "POST":
            payload = json.loads(request.content)
            self.search_payloads.append(payload)
            if "bedrooms" in payload.get("attributes", {}):
                return httpx.Response(200, json={"data": [{"id": 102, "name_th": "B"}]})
            return httpx.Response(200, json={"data": [{"id": 101, "name_th": "A"}, {"id": 102, "name_th": "B"}]})
        if "/catalog/" in path and request.method == "GET":
            item_id = int(path.rsplit("/", 1)[1])
            self.detail_ids.append(item_id)
            return httpx.Response(200, json={"data": {"id": item_id, "usable_area_sqm": 45}})
        return httpx.Response(200, json={"data": []})


def test_process_keeps_catalog_context_and_uses_detail_endpoint() -> None:
    async def scenario() -> _ConversationFlowTransport:
        transport = _ConversationFlowTransport()
        settings = Settings(
            management_base_url="http://management",
            management_token="management-token",
            chatwoot_base_url="http://chatwoot",
            chatwoot_bot_token="chatwoot-token",
            chatwoot_account_id=1,
            chatwoot_team_id=2,
            webhook_token="webhook-token",
            openrouter_api_key="openrouter-key",
            openrouter_model="test-model",
            allowed_inbox_ids=frozenset(),
            redis_url="redis://redis",
        )
        async with httpx.AsyncClient(transport=httpx.MockTransport(transport)) as client:
            events = [
                (1, "มีคอนโดบางนา งบไม่เกิน 4 ล้านไหม"),
                (2, "เอา 2 ห้องนอน"),
                (3, "ตัวแรกกี่ตารางเมตร"),
            ]
            for message_id, content in events:
                await process(settings, {
                    "account": {"id": 1},
                    "message": {
                        "id": message_id,
                        "content": content,
                        "message_type": "incoming",
                        "conversation": {"id": 7},
                    },
                }, client)
        return transport

    transport = asyncio.run(scenario())

    assert transport.search_payloads[0]["category_slug"] == "condo"
    assert transport.search_payloads[0]["location"] == {"text": "บางนา"}
    assert transport.search_payloads[0]["price"] == {"max": 4_000_000.0}
    assert transport.search_payloads[1]["location"] == {"text": "บางนา"}
    assert transport.search_payloads[1]["price"] == {"max": 4_000_000.0}
    assert transport.search_payloads[1]["attributes"] == {"bedrooms": {"gte": 2}}
    assert transport.detail_ids == [102]
