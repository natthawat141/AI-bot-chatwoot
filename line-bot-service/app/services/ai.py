import logging
import re

from openai import OpenAI

from app.config import Settings
from app.services.knowledge import (
    build_live_fallback_reply,
    build_mock_fallback_reply,
    load_knowledge,
    uses_mock_knowledge,
)
from app.services.knowledge_api import EMPTY_SNAPSHOT, KnowledgeApiClient

logger = logging.getLogger("line_bot.ai")

MAX_REPLY_CHARS = 5000  # limit ของ LINE ต่อหนึ่ง text message
FALLBACK_REPLY = "ขอโทษครับ ตอนนี้ระบบขัดข้องชั่วคราว ลองพิมพ์มาใหม่อีกครั้งนะครับ"
MOCK_DATA_DISCLAIMER = (
    "ข้อมูลต่อไปนี้เป็นข้อมูลตัวอย่างสำหรับทดสอบระบบ "
    "ยังไม่ใช่ราคา โปรโมชั่น หรือบริการจริง กรุณาติดต่อคลินิกเพื่อยืนยัน\n\n"
)

SYSTEM_TEMPLATE = (
    "คุณคือผู้ช่วยตอบแชทผ่าน LINE ของคลินิก ที่เป็นมิตร อบอุ่น และสุภาพ "
    "พูดด้วยน้ำเสียงเป็นธรรมชาติเหมือนพนักงานต้อนรับ กระชับแต่ไม่ห้วน "
    "ตอบเป็นภาษาเดียวกับที่ผู้ใช้พิมพ์มา "
    "ถ้าผู้ใช้ทักทายหรือคุยเล่น ให้ทักทายกลับอย่างอบอุ่นสัก 1-2 ประโยค "
    "แล้วชวนต่อว่าสนใจสอบถามเรื่องบริการ แพ็กเกจ โปรโมชั่น หรือข้อมูลคลินิกด้านใด "
    "ตอบเป็นข้อความธรรมดาเท่านั้น ห้ามใช้ markdown ทุกชนิด "
    "(ห้าม **ตัวหนา**, ห้าม # หัวข้อ, ห้าม * นำหน้ารายการ) "
    "ถ้าต้องทำรายการให้ใช้ขีด - หรือเว้นบรรทัด "
    "ข้อเท็จจริงของธุรกิจ เช่น ราคา แพ็กเกจ เวลา บริการ และโปรโมชั่น "
    "ให้ตอบเฉพาะเมื่อมีข้อมูลธุรกิจแนบมาเท่านั้น "
    "ถ้าไม่มีข้อมูลให้บอกว่ายังไม่มีข้อมูลและแนะนำให้ติดต่อคลินิก ห้ามเดา"
)


def strip_markdown(text: str) -> str:
    """ลบ markdown ที่ LLM อาจหลุดมา — เก็บเนื้อหาปกติ (เบอร์โทร ราคา ข้อความไทย) ไว้"""
    if not text:
        return text

    text = re.sub(r"```[^\n]*\n(.*?)```", r"\1", text, flags=re.DOTALL)
    text = re.sub(r"`([^`]+)`", r"\1", text)
    text = re.sub(r"\*\*(.+?)\*\*", r"\1", text)
    text = re.sub(r"__(.+?)__", r"\1", text)
    text = re.sub(r"(?<!\*)\*([^*\n]+)\*(?!\*)", r"\1", text)
    text = re.sub(r"(?<!_)_([^_\n]+)_(?!_)", r"\1", text)
    text = re.sub(r"^#{1,6}\s+", "", text, flags=re.MULTILINE)
    text = re.sub(r"^[*\-]\s+", "- ", text, flags=re.MULTILINE)
    return text


def build_system_prompt(user_text: str | None = None, snapshot=None) -> str:
    knowledge = load_knowledge(user_text, snapshot)
    if not knowledge:
        return SYSTEM_TEMPLATE
    return f"{SYSTEM_TEMPLATE}\n\n# ข้อมูลธุรกิจ\n{knowledge}"


class AIService:
    def __init__(
        self,
        settings: Settings,
        client: OpenAI | None = None,
        knowledge_client: KnowledgeApiClient | None = None,
    ):
        self._settings = settings
        self._knowledge_client = knowledge_client
        self._client = client or OpenAI(
            base_url="https://openrouter.ai/api/v1",
            api_key=settings.OPENROUTER_API_KEY,
            timeout=settings.AI_TIMEOUT_SECONDS,
        )

    def get_reply(self, user_text: str) -> str:
        live_configured = (
            self._knowledge_client is not None and self._knowledge_client.enabled
        )
        snapshot = (
            self._knowledge_client.fetch_snapshot()
            if self._knowledge_client is not None
            else None
        )
        if live_configured and snapshot is None:
            # Live data is configured but unavailable: ground on an empty
            # snapshot (no-guess + human contact) — never revive mock prices.
            snapshot = EMPTY_SNAPSHOT
        contains_mock_data = not live_configured and uses_mock_knowledge(user_text)

        def grounded_fallback() -> str:
            if snapshot is not None:
                fallback = build_live_fallback_reply(user_text, snapshot)
                if fallback:
                    return fallback[:MAX_REPLY_CHARS]
                return FALLBACK_REPLY
            if contains_mock_data:
                fallback = build_mock_fallback_reply(user_text)
                return f"{MOCK_DATA_DISCLAIMER}{fallback}"[:MAX_REPLY_CHARS]
            return FALLBACK_REPLY

        try:
            completion = self._client.chat.completions.create(
                model=self._settings.OPENROUTER_MODEL,
                max_tokens=500,
                messages=[
                    {"role": "system", "content": build_system_prompt(user_text, snapshot)},
                    {"role": "user", "content": user_text},
                ],
            )
            reply = (completion.choices[0].message.content or "").strip()
        except Exception as exc:
            logger.error("AI call failed: %s", type(exc).__name__)
            return grounded_fallback()

        if not reply:
            logger.error("AI returned empty reply")
            return grounded_fallback()
        reply = strip_markdown(reply)
        if contains_mock_data:
            reply = f"{MOCK_DATA_DISCLAIMER}{reply}"
        return reply[:MAX_REPLY_CHARS]
