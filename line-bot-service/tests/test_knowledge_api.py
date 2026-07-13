import io
import json
from unittest.mock import MagicMock

import pytest

from app.config import Settings
from app.services import knowledge_api
from app.services.ai import (
    AIService,
    FALLBACK_REPLY,
    MOCK_DATA_DISCLAIMER,
    build_system_prompt,
)
from app.services.knowledge import build_live_fallback_reply, build_live_knowledge
from app.services.knowledge_api import KnowledgeApiClient, KnowledgeSnapshot


def make_settings(**overrides) -> Settings:
    values = dict(
        LINE_CHANNEL_SECRET="test-secret",
        LINE_CHANNEL_ACCESS_TOKEN="test-token",
        OPENROUTER_API_KEY="test-key",
        KNOWLEDGE_API_URL="http://management.test",
        KNOWLEDGE_API_TOKEN="knowledge-token",
        KNOWLEDGE_CACHE_SECONDS=60.0,
    )
    values.update(overrides)
    return Settings(**values)


SNAPSHOT = KnowledgeSnapshot(
    packages=(
        {
            "code": "KEL-001",
            "name_th": "โปรแกรมดูแลคีลอยด์",
            "price": "1500.00",
            "sale_price": None,
            "currency": "THB",
            "keywords": "คีลอยด์, แผลนูน, keloid",
            "description_th": "แพทย์ประเมินก่อนรับบริการ",
        },
        {
            "code": "LASER-001",
            "name_th": "เลเซอร์ดูแลผิว",
            "price": "990.00",
            "sale_price": "790.00",
            "currency": "THB",
            "keywords": ["เลเซอร์", "ผิว"],
            "description_th": None,
        },
    ),
    faqs=(
        {
            "question_th": "จอดรถได้ที่ไหน",
            "answer_th": "มีที่จอดรถหน้าคลินิก",
            "tags": "จอดรถ, parking",
        },
    ),
    entries=(
        {
            "title": "เวลาเปิดทำการ",
            "body": "เปิดทุกวัน 10:00-20:00",
            "tags": "เวลา, เปิด",
        },
    ),
)


class FakeKnowledgeClient:
    def __init__(self, snapshot: KnowledgeSnapshot | None, enabled: bool = True):
        self._snapshot = snapshot
        self.enabled = enabled

    def fetch_snapshot(self) -> KnowledgeSnapshot | None:
        return self._snapshot if self.enabled else None


def _fake_response(rows: list[dict]):
    body = json.dumps({"meta": {"count": len(rows)}, "data": rows}).encode("utf-8")

    class FakeResponse(io.BytesIO):
        def __enter__(self):
            return self

        def __exit__(self, *args):
            self.close()

    return FakeResponse(body)


# ---------------------------------------------------------------- client


def test_client_disabled_without_token():
    client = KnowledgeApiClient(make_settings(KNOWLEDGE_API_TOKEN=""))
    assert client.enabled is False
    assert client.fetch_snapshot() is None


def test_fetch_snapshot_parses_envelope_and_sends_bearer(monkeypatch):
    captured_requests = []

    def fake_urlopen(request, timeout):
        captured_requests.append(request)
        if "/packages" in request.full_url:
            return _fake_response([{"code": "KEL-001"}])
        return _fake_response([])

    monkeypatch.setattr(knowledge_api, "urlopen", fake_urlopen)
    client = KnowledgeApiClient(make_settings())

    snapshot = client.fetch_snapshot()

    assert snapshot is not None
    assert snapshot.packages == ({"code": "KEL-001"},)
    assert snapshot.faqs == ()
    assert all(
        req.get_header("Authorization") == "Bearer knowledge-token"
        for req in captured_requests
    )
    assert {req.full_url for req in captured_requests} == {
        "http://management.test/api/v1/packages",
        "http://management.test/api/v1/faqs",
        "http://management.test/api/v1/knowledge",
    }


def test_fetch_snapshot_uses_cache_within_ttl(monkeypatch):
    calls = {"count": 0}

    def fake_urlopen(request, timeout):
        calls["count"] += 1
        return _fake_response([])

    monkeypatch.setattr(knowledge_api, "urlopen", fake_urlopen)
    client = KnowledgeApiClient(make_settings())

    first = client.fetch_snapshot()
    second = client.fetch_snapshot()

    assert first is second
    assert calls["count"] == 3  # packages + faqs + knowledge, fetched once


def test_fetch_snapshot_returns_none_on_failure_without_cache(monkeypatch):
    def fake_urlopen(request, timeout):
        raise TimeoutError("down")

    monkeypatch.setattr(knowledge_api, "urlopen", fake_urlopen)
    client = KnowledgeApiClient(make_settings())

    assert client.fetch_snapshot() is None


def test_fetch_snapshot_serves_stale_cache_on_failure(monkeypatch):
    responses = {"fail": False}

    def fake_urlopen(request, timeout):
        if responses["fail"]:
            raise TimeoutError("down")
        return _fake_response([{"code": "KEL-001"}])

    monkeypatch.setattr(knowledge_api, "urlopen", fake_urlopen)
    client = KnowledgeApiClient(make_settings(KNOWLEDGE_CACHE_SECONDS=0.0))

    first = client.fetch_snapshot()
    responses["fail"] = True
    second = client.fetch_snapshot()

    assert first is not None
    assert second is first


def test_stale_cache_rejected_past_max_stale(monkeypatch):
    clock = {"now": 1000.0}
    monkeypatch.setattr(knowledge_api, "monotonic", lambda: clock["now"])

    responses = {"fail": False}

    def fake_urlopen(request, timeout):
        if responses["fail"]:
            raise TimeoutError("down")
        return _fake_response([{"code": "KEL-001"}])

    monkeypatch.setattr(knowledge_api, "urlopen", fake_urlopen)
    client = KnowledgeApiClient(make_settings(KNOWLEDGE_CACHE_SECONDS=60.0))

    assert client.fetch_snapshot() is not None
    responses["fail"] = True

    clock["now"] += knowledge_api.MAX_STALE_SECONDS - 1
    assert client.fetch_snapshot() is not None  # stale but within cap

    clock["now"] += knowledge_api.FAILURE_BACKOFF_SECONDS + 2  # past cap now
    assert client.fetch_snapshot() is None


def test_failure_backoff_skips_refetch(monkeypatch):
    clock = {"now": 1000.0}
    monkeypatch.setattr(knowledge_api, "monotonic", lambda: clock["now"])

    calls = {"count": 0}

    def fake_urlopen(request, timeout):
        calls["count"] += 1
        raise TimeoutError("down")

    monkeypatch.setattr(knowledge_api, "urlopen", fake_urlopen)
    client = KnowledgeApiClient(make_settings())

    client.fetch_snapshot()
    attempts_after_first = calls["count"]
    client.fetch_snapshot()  # within backoff — no new HTTP attempt

    assert calls["count"] == attempts_after_first

    clock["now"] += knowledge_api.FAILURE_BACKOFF_SECONDS + 1
    client.fetch_snapshot()  # backoff elapsed — retries

    assert calls["count"] > attempts_after_first


def test_fetch_snapshot_rejects_bad_payload(monkeypatch):
    def fake_urlopen(request, timeout):
        class FakeResponse(io.BytesIO):
            def __enter__(self):
                return self

            def __exit__(self, *args):
                self.close()

        return FakeResponse(b'{"data": "not-a-list"}')

    monkeypatch.setattr(knowledge_api, "urlopen", fake_urlopen)
    client = KnowledgeApiClient(make_settings())

    assert client.fetch_snapshot() is None


# ---------------------------------------------------------------- selection


def test_live_knowledge_selects_matching_package():
    knowledge = build_live_knowledge("คีลอยด์ราคาเท่าไร", SNAPSHOT)

    assert "[LIVE_DATA]" in knowledge
    assert "KEL-001" in knowledge
    assert "LASER-001" not in knowledge
    assert "[MOCK_DATA]" not in knowledge


def test_live_knowledge_generic_question_gets_catalog():
    knowledge = build_live_knowledge("มีบริการอะไรบ้าง", SNAPSHOT)

    assert "KEL-001" in knowledge
    assert "LASER-001" in knowledge


def test_live_knowledge_includes_faq_and_entries():
    knowledge = build_live_knowledge("จอดรถได้ไหม", SNAPSHOT)

    assert "จอดรถหน้าคลินิก" in knowledge

    knowledge = build_live_knowledge("เวลาเปิดกี่โมง", SNAPSHOT)
    assert "10:00-20:00" in knowledge


def test_live_knowledge_shows_sale_price():
    knowledge = build_live_knowledge("เลเซอร์ราคาเท่าไร", SNAPSHOT)

    assert "ราคาพิเศษ 790 THB" in knowledge
    assert "ปกติ 990 THB" in knowledge


def test_package_line_handles_zero_and_missing_prices():
    snapshot = KnowledgeSnapshot(
        packages=(
            {
                "code": "ZERO-001",
                "name_th": "ทดสอบราคา",
                "price": None,
                "sale_price": "0.00",
                "currency": "THB",
                "keywords": "ทดสอบราคา",
            },
        ),
        faqs=(),
        entries=(),
    )
    knowledge = build_live_knowledge("ทดสอบราคา", snapshot)

    assert "0.00 THB" not in knowledge
    assert "None" not in knowledge


def test_faq_matches_by_question_text_without_tags():
    snapshot = KnowledgeSnapshot(
        packages=(),
        faqs=(
            {
                "question_th": "จอดรถได้ที่ไหน",
                "answer_th": "มีที่จอดรถหน้าคลินิก",
                "tags": None,
            },
        ),
        entries=(),
    )
    knowledge = build_live_knowledge("จอดรถ", snapshot)

    assert "จอดรถหน้าคลินิก" in knowledge


def test_truncated_catalog_gets_note():
    packages = tuple(
        {"code": f"PKG-{i:03d}", "name_th": f"แพ็กเกจ {i}", "price": "100.00", "keywords": ""}
        for i in range(20)
    )
    snapshot = KnowledgeSnapshot(packages=packages, faqs=(), entries=())
    knowledge = build_live_knowledge("มีบริการอะไรบ้าง", snapshot)

    assert "รายการข้างต้นเป็นเพียงบางส่วน" in knowledge


def test_live_fallback_reply_lists_matched_records():
    reply = build_live_fallback_reply("คีลอยด์", SNAPSHOT)

    assert reply is not None
    assert reply.startswith("ข้อมูลที่เกี่ยวข้องจากระบบ:")
    assert "KEL-001" in reply


def test_empty_snapshot_prompts_no_guess():
    empty = KnowledgeSnapshot(packages=(), faqs=(), entries=())
    knowledge = build_live_knowledge("ราคาเท่าไร", empty)

    assert knowledge is not None
    assert "ยังไม่มีรายการที่เปิดใช้งาน" in knowledge


def test_greeting_omits_live_catalog():
    # A plain greeting has no business intent and matches no record, so no
    # package/promo data should be pulled into the prompt.
    assert build_live_knowledge("สวัสดีครับ", SNAPSHOT) is None

    prompt = build_system_prompt("สวัสดีครับ", SNAPSHOT)
    assert "[LIVE_DATA]" not in prompt
    assert "KEL-001" not in prompt
    assert "W+ Medic Clinic" in prompt  # verified contact info still present


def test_package_question_pulls_live_data():
    prompt = build_system_prompt("มีโปรโมชั่นอะไรบ้าง", SNAPSHOT)
    assert "[LIVE_DATA]" in prompt


# ---------------------------------------------------------------- AIService


def _mock_completion(content: str | None):
    message = MagicMock()
    message.content = content
    choice = MagicMock()
    choice.message = message
    completion = MagicMock()
    completion.choices = [choice]
    return completion


def test_live_reply_has_no_mock_disclaimer_and_live_prompt():
    llm = MagicMock()
    llm.chat.completions.create.return_value = _mock_completion(
        "โปรแกรมดูแลคีลอยด์ ราคา 1,500 บาท"
    )
    service = AIService(
        make_settings(), client=llm, knowledge_client=FakeKnowledgeClient(SNAPSHOT)
    )

    result = service.get_reply("แพ็กเกจคีลอยด์ราคาเท่าไร")

    assert not result.startswith(MOCK_DATA_DISCLAIMER)
    prompt = llm.chat.completions.create.call_args.kwargs["messages"][0]["content"]
    assert "[LIVE_DATA]" in prompt
    assert "[MOCK_DATA]" not in prompt
    assert "KEL-001" in prompt


def test_live_ai_error_returns_grounded_live_fallback():
    llm = MagicMock()
    llm.chat.completions.create.side_effect = TimeoutError("timeout")
    service = AIService(
        make_settings(), client=llm, knowledge_client=FakeKnowledgeClient(SNAPSHOT)
    )

    result = service.get_reply("คีลอยด์ราคาเท่าไร")

    assert "KEL-001" in result
    assert not result.startswith(MOCK_DATA_DISCLAIMER)
    assert result != FALLBACK_REPLY


def test_client_disabled_falls_back_to_mock_behavior():
    llm = MagicMock()
    llm.chat.completions.create.return_value = _mock_completion("ตอบจาก mock")
    service = AIService(
        make_settings(),
        client=llm,
        knowledge_client=FakeKnowledgeClient(None, enabled=False),
    )

    result = service.get_reply("แพ็กเกจคีลอยด์ราคาเท่าไร")

    assert result.startswith(MOCK_DATA_DISCLAIMER)
    prompt = llm.chat.completions.create.call_args.kwargs["messages"][0]["content"]
    assert "[MOCK_DATA]" in prompt


def test_enabled_but_down_never_revives_mock():
    """Live API configured but unreachable: no-guess grounding, no mock prices."""
    llm = MagicMock()
    llm.chat.completions.create.return_value = _mock_completion("ยังไม่มีข้อมูลครับ")
    service = AIService(
        make_settings(), client=llm, knowledge_client=FakeKnowledgeClient(None)
    )

    result = service.get_reply("แพ็กเกจคีลอยด์ราคาเท่าไร")

    assert not result.startswith(MOCK_DATA_DISCLAIMER)
    prompt = llm.chat.completions.create.call_args.kwargs["messages"][0]["content"]
    assert "[MOCK_DATA]" not in prompt
    assert "MOCK-KEL-001" not in prompt
    assert "ยังไม่มีรายการที่เปิดใช้งาน" in prompt


def test_enabled_but_down_ai_error_returns_plain_fallback():
    llm = MagicMock()
    llm.chat.completions.create.side_effect = TimeoutError("timeout")
    service = AIService(
        make_settings(), client=llm, knowledge_client=FakeKnowledgeClient(None)
    )

    result = service.get_reply("แพ็กเกจคีลอยด์ราคาเท่าไร")

    assert result == FALLBACK_REPLY
    assert "MOCK-KEL-001" not in result


def test_build_system_prompt_with_snapshot_uses_live_data():
    prompt = build_system_prompt("คีลอยด์", SNAPSHOT)

    assert "[LIVE_DATA]" in prompt
    assert "W+ Medic Clinic" in prompt
