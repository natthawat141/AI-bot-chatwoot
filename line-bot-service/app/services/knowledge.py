"""Verified bootstrap knowledge for the LINE bot.

The management application will become the source of truth. Keeping this
small record lets the bot answer essential clinic questions while the CRUD
application is unavailable during development.
"""

from dataclasses import dataclass


@dataclass(frozen=True)
class ClinicLocation:
    title: str
    address: str
    latitude: float
    longitude: float


PRIMARY_LOCATION = ClinicLocation(
    title="W+ Medic Clinic - Bang Yai",
    address=(
        "56/19-20 Moo 15, Rattanathibet Road, Bang Rak Phatthana, "
        "Bang Bua Thong, Nonthaburi 11110"
    ),
    latitude=13.8752532,
    longitude=100.4223009,
)

LOCATION_INTENTS = (
    "อยู่ไหน",
    "ที่อยู่",
    "พิกัด",
    "แผนที่",
    "เดินทาง",
    "location",
    "address",
    "map",
    "direction",
)

MOCK_KNOWLEDGE_INTENTS = (
    "ราคา",
    "แพ็กเกจ",
    "โปรโมชั่น",
    "โปร",
    "บริการ",
    "คีลอยด์",
    "แผลเป็น",
    "สิว",
    "เลเซอร์",
    "price",
    "package",
    "promotion",
    "service",
    "keloid",
    "laser",
)

VERIFIED_KNOWLEDGE = """W+ Medic Clinic สาขาบางใหญ่
ที่อยู่: 56/19-20 หมู่ 15 ถนนรัตนาธิเบศร์ ตำบลบางรักพัฒนา อำเภอบางบัวทอง จังหวัดนนทบุรี 11110
จุดสังเกต: ติดกับสถานี MRT สามแยกบางใหญ่
Call Center: 095-696-0966
โทรศัพท์สาขาบางใหญ่: 02-126-0408
LINE ID: @Wmedic
เว็บไซต์ทางการ: https://www.wmedicclinic.com/
Google Maps: https://maps.app.goo.gl/473PDwRaZs7hzeQR7
บริการหลัก: เลเซอร์และผิวหนัง, บริการสำหรับผู้หญิง, บริการสำหรับผู้ชาย, การดูแลสุขภาพ, ศัลยกรรมขนาดเล็กและเวชศาสตร์ความงาม, การแพทย์และวัคซีน
หมายเหตุ: หากไม่มีข้อมูลราคา เวลาเปิดทำการ หรือรายละเอียดการรักษาที่ผ่านการยืนยัน ให้แจ้งว่ายังไม่มีข้อมูลและแนะนำให้ติดต่อคลินิก ห้ามคาดเดา"""

@dataclass(frozen=True)
class MockKnowledgeRecord:
    content: str
    aliases: tuple[str, ...]


MOCK_PACKAGES = (
    MockKnowledgeRecord(
        "MOCK-KEL-001 โปรแกรมตัวอย่างประเมินคีลอยด์ ราคา 1,500 บาท; แพทย์ประเมินก่อนรับบริการ",
        ("คีลอยด์", "คีรอย", "แผลนูน", "แผลเป็นนูน", "keloid"),
    ),
    MockKnowledgeRecord(
        "MOCK-KEL-002 โปรแกรมตัวอย่างดูแลแผลเป็นเฉพาะจุด ราคา 3,500 บาท; จำนวนครั้งขึ้นอยู่กับการประเมิน",
        ("แผลเป็น", "รอยแผล", "แผลผ่าตัด", "scar"),
    ),
    MockKnowledgeRecord(
        "MOCK-ACNE-001 โปรแกรมตัวอย่างดูแลหลุมสิว ราคา 2,900 บาท; ต้องประเมินสภาพผิวก่อน",
        ("หลุมสิว", "รอยสิว", "acne scar"),
    ),
    MockKnowledgeRecord(
        "MOCK-ACNE-002 โปรแกรมตัวอย่างดูแลสิว 5 ขั้นตอน ราคา 1,290 บาท",
        ("สิว", "กดสิว", "ฉายแสง", "acne"),
    ),
    MockKnowledgeRecord(
        "MOCK-LASER-001 โปรแกรมตัวอย่างเลเซอร์ดูแลผิว ราคา 990 บาท",
        ("เลเซอร์", "ผิว", "จุดด่างดำ", "laser", "skin"),
    ),
    MockKnowledgeRecord(
        "MOCK-HAIR-001 โปรแกรมตัวอย่างเลเซอร์กำจัดขน ราคาเริ่มต้น 799 บาท",
        ("กำจัดขน", "ขน", "hair removal"),
    ),
    MockKnowledgeRecord(
        "MOCK-MEN-001 โปรแกรมตัวอย่างให้คำปรึกษาสุขภาพสำหรับผู้ชาย ราคา 1,200 บาท",
        ("ผู้ชาย", "for men", "men"),
    ),
    MockKnowledgeRecord(
        "MOCK-LADY-001 โปรแกรมตัวอย่างให้คำปรึกษาสุขภาพสำหรับผู้หญิง ราคา 1,200 บาท",
        ("ผู้หญิง", "for lady", "lady", "women"),
    ),
    MockKnowledgeRecord(
        "MOCK-WELL-001 แพ็กเกจตัวอย่างตรวจสุขภาพพื้นฐาน ราคา 1,990 บาท",
        ("ตรวจสุขภาพ", "สุขภาพ", "wellness", "health check"),
    ),
    MockKnowledgeRecord(
        "MOCK-VAX-001 บริการตัวอย่างปรึกษาเรื่องวัคซีน ราคา 500 บาท; ยังไม่รวมค่าวัคซีน",
        ("วัคซีน", "vaccine"),
    ),
)

MOCK_FAQS = (
    MockKnowledgeRecord(
        "MOCK-FAQ-001 การจองคิว: ตัวอย่างกำหนดให้จองล่วงหน้าอย่างน้อย 1 วัน",
        ("จอง", "นัด", "คิว", "booking"),
    ),
    MockKnowledgeRecord(
        "MOCK-FAQ-002 ที่จอดรถ: ตัวอย่างระบุว่ามีที่จอดรถจำนวนจำกัด",
        ("จอดรถ", "ที่จอด", "parking"),
    ),
    MockKnowledgeRecord(
        "MOCK-FAQ-003 การชำระเงิน: ตัวอย่างรองรับเงินสด บัตร และโอนเงิน",
        ("ชำระ", "จ่าย", "บัตรเครดิต", "payment"),
    ),
    MockKnowledgeRecord(
        "MOCK-FAQ-004 การเตรียมตัว: ตัวอย่างแนะนำให้งดผลิตภัณฑ์ระคายเคืองและแจ้งประวัติแพ้ยา โดยต้องยืนยันกับเจ้าหน้าที่",
        ("เตรียมตัว", "ก่อนทำ", "แพ้ยา", "prepare"),
    ),
    MockKnowledgeRecord(
        "MOCK-FAQ-005 การเลื่อนนัด: ตัวอย่างกำหนดให้แจ้งล่วงหน้าอย่างน้อย 24 ชั่วโมง",
        ("เลื่อนนัด", "ยกเลิก", "reschedule", "cancel"),
    ),
    MockKnowledgeRecord(
        "MOCK-FAQ-006 โปรโมชั่นตัวอย่างไม่สามารถใช้ร่วมกับส่วนลดอื่นได้",
        ("โปรโมชั่น", "โปร", "ส่วนลด", "promotion"),
    ),
    MockKnowledgeRecord(
        "MOCK-FAQ-007 ผลลัพธ์และจำนวนครั้งขึ้นอยู่กับการประเมินของแพทย์ ห้ามรับรองผล",
        ("หายไหม", "กี่ครั้ง", "ผลลัพธ์", "result"),
    ),
    MockKnowledgeRecord(
        "MOCK-FAQ-008 กรณีตั้งครรภ์ มีโรคประจำตัว หรือใช้ยาอยู่ ต้องปรึกษาแพทย์ก่อนรับบริการ",
        ("ตั้งครรภ์", "โรคประจำตัว", "กินยา", "pregnant"),
    ),
)

MOCK_DATA_HEADER = """[MOCK_DATA]
ข้อมูลต่อไปนี้สร้างขึ้นเพื่อทดสอบระบบเท่านั้น ไม่ใช่ราคา โปรโมชั่น นโยบาย หรือบริการจริงของคลินิก
ห้ามรับรองผลการรักษา และต้องแจ้งผู้ใช้เสมอว่ารายการเหล่านี้เป็นข้อมูลตัวอย่าง"""


def is_location_query(text: str) -> bool:
    normalized = text.casefold().strip()
    return any(keyword in normalized for keyword in LOCATION_INTENTS)


def get_primary_location() -> ClinicLocation:
    return PRIMARY_LOCATION


def uses_mock_knowledge(text: str) -> bool:
    normalized = text.casefold().strip()
    return any(keyword in normalized for keyword in MOCK_KNOWLEDGE_INTENTS)


def has_knowledge_intent(text: str | None) -> bool:
    """True when the message asks about business facts (price, package, service, promo)."""
    normalized = (text or "").casefold().strip()
    return any(keyword in normalized for keyword in MOCK_KNOWLEDGE_INTENTS)


def load_relevant_mock_knowledge(user_text: str) -> str:
    normalized = user_text.casefold().strip()
    records = MOCK_PACKAGES + MOCK_FAQS
    matches = [
        record
        for record in records
        if any(alias in normalized for alias in record.aliases)
    ]
    if not matches:
        matches = list(MOCK_PACKAGES)
    lines = "\n".join(f"- {record.content}" for record in matches[:10])
    return f"{MOCK_DATA_HEADER}\n{lines}"


def build_mock_fallback_reply(user_text: str) -> str:
    """Build a safe reply when a development model is unavailable."""
    normalized = user_text.casefold().strip()
    records = MOCK_PACKAGES + MOCK_FAQS
    matches = [
        record
        for record in records
        if any(alias in normalized for alias in record.aliases)
    ]
    if not matches:
        matches = list(MOCK_PACKAGES[:5])
    lines = "\n".join(f"- {record.content}" for record in matches[:5])
    return f"รายการข้อมูลตัวอย่างที่เกี่ยวข้อง:\n{lines}"


LIVE_DATA_HEADER = """[LIVE_DATA]
ข้อมูลต่อไปนี้มาจากระบบจัดการของคลินิก เป็นข้อมูลจริงที่เปิดใช้งานอยู่
ให้ตอบโดยอ้างอิงเฉพาะรายการเหล่านี้ ถ้าคำถามไม่ตรงกับรายการใดให้แจ้งว่ายังไม่มีข้อมูลและแนะนำให้ติดต่อคลินิก ห้ามคาดเดา"""

_MAX_LIVE_PACKAGES = 12
_MAX_LIVE_FAQS = 8
_MAX_LIVE_ENTRIES = 8
_MAX_FIELD_CHARS = 300


def _terms(value) -> tuple[str, ...]:
    """Normalize a keywords/tags field that may be a list or a comma-separated string."""
    if isinstance(value, str):
        parts = value.replace(";", ",").split(",")
    elif isinstance(value, (list, tuple)):
        parts = [str(part) for part in value]
    else:
        return ()
    return tuple(p for p in (part.casefold().strip() for part in parts) if p)


def _clip(value) -> str:
    text = " ".join(str(value or "").split())
    if len(text) > _MAX_FIELD_CHARS:
        return text[:_MAX_FIELD_CHARS] + "…"
    return text


def _matches(
    normalized_text: str, terms: tuple[str, ...], *, bidirectional: bool = False
) -> bool:
    for term in terms:
        if term in normalized_text:
            return True
        # For FAQ questions / entry titles, a short user message contained in
        # the question ("จอดรถ" in "จอดรถได้ที่ไหน") should also match.
        if bidirectional and len(normalized_text) >= 4 and normalized_text in term:
            return True
    return False


def _price_amount(value) -> float | None:
    """Laravel decimal casts arrive as strings; treat None/0/garbage as absent."""
    try:
        amount = float(value)
    except (TypeError, ValueError):
        return None
    return amount if amount > 0 else None


def _format_amount(amount: float) -> str:
    return f"{amount:,.0f}" if amount == int(amount) else f"{amount:,.2f}"


def _package_line(package: dict) -> str:
    name = _clip(package.get("name_th") or package.get("name_en") or "")
    code = _clip(package.get("code") or "")
    price = _price_amount(package.get("price"))
    sale_price = _price_amount(package.get("sale_price"))
    currency = _clip(package.get("currency") or "THB")

    parts = [p for p in (code, name) if p]
    if sale_price is not None and price is not None:
        parts.append(
            f"ราคาพิเศษ {_format_amount(sale_price)} {currency}"
            f" (ปกติ {_format_amount(price)} {currency})"
        )
    elif sale_price is not None:
        parts.append(f"ราคาพิเศษ {_format_amount(sale_price)} {currency}")
    elif price is not None:
        parts.append(f"ราคา {_format_amount(price)} {currency}")
    if description := _clip(package.get("description_th")):
        parts.append(description)
    return "- " + "; ".join(parts)


def _faq_line(faq: dict) -> str:
    question = _clip(faq.get("question_th") or faq.get("question_en"))
    answer = _clip(faq.get("answer_th") or faq.get("answer_en"))
    return f"- ถาม: {question} ตอบ: {answer}"


def _entry_line(entry: dict) -> str:
    title = _clip(entry.get("title"))
    body = _clip(entry.get("body"))
    return f"- {title}: {body}"


def _select_live_records(
    user_text: str | None, snapshot
) -> tuple[list[dict], list[dict], list[dict], bool]:
    normalized = (user_text or "").casefold().strip()

    def package_terms(package: dict) -> tuple[str, ...]:
        extra = tuple(
            str(value).casefold()
            for value in (package.get("code"), package.get("name_th"), package.get("name_en"))
            if value
        )
        return _terms(package.get("keywords")) + extra

    def faq_terms(faq: dict) -> tuple[str, ...]:
        questions = tuple(
            str(value).casefold()
            for value in (faq.get("question_th"), faq.get("question_en"))
            if value
        )
        return _terms(faq.get("tags")) + questions

    def entry_terms(entry: dict) -> tuple[str, ...]:
        title = str(entry.get("title") or "").casefold()
        return _terms(entry.get("tags")) + ((title,) if title else ())

    packages = [p for p in snapshot.packages if normalized and _matches(normalized, package_terms(p))]
    faqs = [
        f
        for f in snapshot.faqs
        if normalized and _matches(normalized, faq_terms(f), bidirectional=True)
    ]
    entries = [
        e
        for e in snapshot.entries
        if normalized and _matches(normalized, entry_terms(e), bidirectional=True)
    ]

    # Nothing matched a specific record. Only expose the full catalog when the
    # message actually asks about services/prices/promos (e.g. "มีบริการอะไรบ้าง")
    # — a plain greeting should not pull package data into the prompt.
    if not packages and not faqs and not entries and has_knowledge_intent(normalized):
        packages = list(snapshot.packages)

    truncated = (
        len(packages) > _MAX_LIVE_PACKAGES
        or len(faqs) > _MAX_LIVE_FAQS
        or len(entries) > _MAX_LIVE_ENTRIES
    )
    return (
        packages[:_MAX_LIVE_PACKAGES],
        faqs[:_MAX_LIVE_FAQS],
        entries[:_MAX_LIVE_ENTRIES],
        truncated,
    )


def build_live_knowledge(user_text: str | None, snapshot) -> str | None:
    """Format relevant records from a management snapshot for the system prompt.

    Returns None for casual messages with no business intent and no matched
    record, so greetings/chit-chat are answered warmly without injecting the
    live catalog.
    """
    packages, faqs, entries, truncated = _select_live_records(user_text, snapshot)
    has_records = bool(packages or faqs or entries)

    if not has_records and not has_knowledge_intent(user_text):
        return None

    sections = [LIVE_DATA_HEADER]
    if packages:
        sections.append("แพ็กเกจ/บริการ:\n" + "\n".join(_package_line(p) for p in packages))
    if faqs:
        sections.append("คำถามที่พบบ่อย:\n" + "\n".join(_faq_line(f) for f in faqs))
    if entries:
        sections.append("ข้อมูลเพิ่มเติม:\n" + "\n".join(_entry_line(e) for e in entries))
    if not has_records:
        sections.append(
            "(ยังไม่มีรายการที่เปิดใช้งานในระบบตรงกับคำถามนี้ "
            "ให้แจ้งผู้ใช้ว่ายังไม่มีข้อมูลและแนะนำให้ติดต่อคลินิก ห้ามเดา)"
        )
    if truncated:
        sections.append(
            "หมายเหตุ: รายการข้างต้นเป็นเพียงบางส่วน ยังมีบริการอื่นอีก "
            "หากไม่พบที่ต้องการให้แนะนำผู้ใช้สอบถามเพิ่มเติมกับคลินิก"
        )
    return "\n\n".join(sections)


def build_live_fallback_reply(user_text: str | None, snapshot) -> str | None:
    """Deterministic grounded reply from live records when the AI model fails."""
    packages, faqs, entries, _truncated = _select_live_records(user_text, snapshot)
    lines = (
        [_package_line(p) for p in packages[:5]]
        + [_faq_line(f) for f in faqs[:3]]
        + [_entry_line(e) for e in entries[:3]]
    )
    if not lines:
        return None
    return "ข้อมูลที่เกี่ยวข้องจากระบบ:\n" + "\n".join(lines)


def load_knowledge(user_text: str | None = None, snapshot=None) -> str:
    """Return verified facts plus live records when available, mock otherwise."""
    if snapshot is not None:
        live_knowledge = build_live_knowledge(user_text, snapshot)
        if live_knowledge is None:
            return VERIFIED_KNOWLEDGE
        return f"{VERIFIED_KNOWLEDGE}\n\n{live_knowledge}"
    if user_text and uses_mock_knowledge(user_text):
        mock_knowledge = load_relevant_mock_knowledge(user_text)
        return f"{VERIFIED_KNOWLEDGE}\n\n{mock_knowledge}"
    return VERIFIED_KNOWLEDGE
