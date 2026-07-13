from unittest.mock import MagicMock

import pytest

from app.config import Settings
from app.services.ai import (
    AIService,
    FALLBACK_REPLY,
    MAX_REPLY_CHARS,
    MOCK_DATA_DISCLAIMER,
    SYSTEM_TEMPLATE,
    build_system_prompt,
    strip_markdown,
)
from app.services.knowledge import load_knowledge


@pytest.fixture
def settings() -> Settings:
    return Settings(
        LINE_CHANNEL_SECRET="test-secret",
        LINE_CHANNEL_ACCESS_TOKEN="test-token",
        OPENROUTER_API_KEY="test-key",
        AI_TIMEOUT_SECONDS=5.0,
    )


def _mock_completion(content: str | None):
    message = MagicMock()
    message.content = content
    choice = MagicMock()
    choice.message = message
    completion = MagicMock()
    completion.choices = [choice]
    return completion


def test_build_system_prompt_includes_verified_knowledge():
    prompt = build_system_prompt()
    knowledge = load_knowledge()
    assert SYSTEM_TEMPLATE in prompt
    assert "W+ Medic Clinic" in knowledge
    assert knowledge in prompt


def test_mock_knowledge_selects_relevant_package():
    knowledge = load_knowledge("รักษาคีรอยราคาเท่าไร")

    assert "[MOCK_DATA]" in knowledge
    assert "MOCK-KEL-001" in knowledge
    assert "MOCK-HAIR-001" not in knowledge


def test_generic_service_question_gets_mock_catalog():
    knowledge = load_knowledge("มีบริการอะไรบ้าง")

    assert "MOCK-KEL-001" in knowledge
    assert "MOCK-ACNE-001" in knowledge
    assert "MOCK-WELL-001" in knowledge


def test_ai_error_returns_fallback(settings):
    client = MagicMock()
    client.chat.completions.create.side_effect = TimeoutError("timeout")
    service = AIService(settings, client=client)

    assert service.get_reply("สวัสดี") == FALLBACK_REPLY


def test_ai_error_with_mock_query_returns_grounded_mock_fallback(settings):
    client = MagicMock()
    client.chat.completions.create.side_effect = TimeoutError("timeout")
    service = AIService(settings, client=client)

    result = service.get_reply("แพ็กเกจคีลอยด์ราคาเท่าไร")

    assert result.startswith(MOCK_DATA_DISCLAIMER)
    assert "MOCK-KEL-001" in result
    assert result != FALLBACK_REPLY


def test_ai_empty_reply_returns_fallback(settings):
    client = MagicMock()
    client.chat.completions.create.return_value = _mock_completion("   ")
    service = AIService(settings, client=client)

    assert service.get_reply("สวัสดี") == FALLBACK_REPLY


def test_ai_empty_reply_with_mock_query_returns_grounded_mock_fallback(settings):
    client = MagicMock()
    client.chat.completions.create.return_value = _mock_completion("   ")
    service = AIService(settings, client=client)

    result = service.get_reply("มีบริการอะไรบ้าง")

    assert result.startswith(MOCK_DATA_DISCLAIMER)
    assert "MOCK-KEL-001" in result


def test_ai_truncates_reply_over_5000_chars(settings):
    long_reply = "ก" * (MAX_REPLY_CHARS + 500)
    client = MagicMock()
    client.chat.completions.create.return_value = _mock_completion(long_reply)
    service = AIService(settings, client=client)

    result = service.get_reply("สวัสดี")
    assert len(result) == MAX_REPLY_CHARS
    assert result == long_reply[:MAX_REPLY_CHARS]


def test_ai_success_returns_reply(settings):
    client = MagicMock()
    client.chat.completions.create.return_value = _mock_completion(
        "สวัสดีครับ ยินดีให้บริการ"
    )
    service = AIService(settings, client=client)

    assert service.get_reply("สวัสดี") == "สวัสดีครับ ยินดีให้บริการ"


def test_mock_package_reply_always_has_disclaimer(settings):
    client = MagicMock()
    client.chat.completions.create.return_value = _mock_completion(
        "โปรแกรมตัวอย่างดูแลคีลอยด์ ราคา 1,500 บาท"
    )
    service = AIService(settings, client=client)

    result = service.get_reply("แพ็กเกจคีลอยด์ราคาเท่าไร")

    assert result.startswith(MOCK_DATA_DISCLAIMER)
    prompt = client.chat.completions.create.call_args.kwargs["messages"][0]["content"]
    assert "[MOCK_DATA]" in prompt


def test_verified_contact_reply_has_no_mock_disclaimer(settings):
    client = MagicMock()
    client.chat.completions.create.return_value = _mock_completion(
        "โทร 095-696-0966"
    )
    service = AIService(settings, client=client)

    result = service.get_reply("เบอร์ติดต่ออะไร")

    assert not result.startswith(MOCK_DATA_DISCLAIMER)


def test_strip_markdown_removes_bold():
    assert strip_markdown("**สวัสดี** ครับ") == "สวัสดี ครับ"
    assert strip_markdown("__ยินดีต้อนรับ__") == "ยินดีต้อนรับ"


def test_strip_markdown_removes_headers():
    assert strip_markdown("# บริการ\n## ราคา\nเนื้อหา") == "บริการ\nราคา\nเนื้อหา"


def test_strip_markdown_normalizes_bullets():
    assert strip_markdown("* รายการแรก\n* รายการสอง") == "- รายการแรก\n- รายการสอง"
    assert strip_markdown("- มีอยู่แล้ว") == "- มีอยู่แล้ว"


def test_strip_markdown_preserves_thai():
    text = "สวัสดีครับ ยินดีให้บริการ"
    assert strip_markdown(text) == text


def test_strip_markdown_preserves_phone_and_price():
    text = "โทร 02-123-4567 หรือ 081-234-5678 ราคา 1,500 บาท"
    assert strip_markdown(text) == text


def test_strip_markdown_removes_inline_code():
    assert strip_markdown("ใช้คำสั่ง `npm install` ได้") == "ใช้คำสั่ง npm install ได้"


def test_get_reply_strips_markdown_from_llm_output(settings):
    markdown_reply = "**สวัสดีครับ**\n# บริการ\n* ตรวจสุขภาพ\n* วัคซีน"
    client = MagicMock()
    client.chat.completions.create.return_value = _mock_completion(markdown_reply)
    service = AIService(settings, client=client)

    result = service.get_reply("สวัสดี")

    assert result == "สวัสดีครับ\nบริการ\n- ตรวจสุขภาพ\n- วัคซีน"
    assert "**" not in result
    assert "#" not in result
