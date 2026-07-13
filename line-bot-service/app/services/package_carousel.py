"""Build LINE Flex Message carousels from active knowledge packages."""

from decimal import Decimal, InvalidOperation
from urllib.parse import urlparse

from linebot.v3.messaging import (
    FlexBox,
    FlexBubble,
    FlexButton,
    FlexCarousel,
    FlexImage,
    FlexMessage,
    FlexSeparator,
    FlexText,
    MessageAction,
)

MAX_CAROUSEL_ITEMS = 10

PACKAGE_MENU_QUERIES = {
    "ดูบริการและแพ็กเกจ",
    "ดูบริการและแพคเกจ",
    "ดูแพ็กเกจ",
    "ดูแพคเกจ",
}
PROMOTION_MENU_QUERIES = {"ดูโปรโมชัน", "ดูโปรโมชั่น"}


def carousel_kind(text: str) -> str | None:
    normalized = " ".join((text or "").casefold().split())
    if normalized in PACKAGE_MENU_QUERIES:
        return "packages"
    if normalized in PROMOTION_MENU_QUERIES:
        return "promotions"
    return None


def select_packages(packages: tuple[dict, ...], kind: str) -> list[dict]:
    rows = list(packages)
    if kind == "promotions":
        rows = [row for row in rows if row.get("sale_price") is not None]
    return rows[:MAX_CAROUSEL_ITEMS]


def build_package_carousel(packages: list[dict], kind: str) -> FlexMessage:
    title = "โปรโมชัน W+ Medic" if kind == "promotions" else "บริการและแพ็กเกจ W+ Medic"
    return FlexMessage(
        altText=title,
        contents=FlexCarousel(contents=[_bubble(row) for row in packages]),
    )


def _bubble(package: dict) -> FlexBubble:
    name = _clip(package.get("name_th") or package.get("name_en") or "แพ็กเกจ", 80)
    code = _clip(package.get("code") or "", 40)
    category = _clip(package.get("category") or "W+ Medic Clinic", 40)
    description = _clip(package.get("description_th") or "สอบถามรายละเอียดเพิ่มเติมกับคลินิก", 160)
    regular_price = _price(package.get("price"))
    sale_price = _price(package.get("sale_price"))
    display_price = sale_price or regular_price or "สอบถามราคา"
    action_text = _clip(f"สนใจแพ็กเกจ {code or name}", 300)

    body_contents = [
        FlexText(text=category, size="xs", color="#8A6D3B", weight="bold", wrap=True),
        FlexText(text=name, size="lg", color="#1F2937", weight="bold", wrap=True, margin="sm", maxLines=3),
        FlexText(text=description, size="sm", color="#6B7280", wrap=True, margin="md", maxLines=4),
        FlexSeparator(margin="lg", color="#E7D9C4"),
    ]
    if sale_price and regular_price and sale_price != regular_price:
        body_contents.extend(
            [
                FlexText(
                    text=f"ปกติ {regular_price}",
                    size="xs",
                    color="#9CA3AF",
                    decoration="line-through",
                    margin="lg",
                ),
                FlexText(text=sale_price, size="xl", color="#B88746", weight="bold", margin="xs"),
            ]
        )
    else:
        body_contents.append(
            FlexText(text=display_price, size="xl", color="#B88746", weight="bold", margin="lg")
        )

    hero = None
    image_url = package.get("image_url")
    if _is_https_url(image_url):
        hero = FlexImage(
            url=image_url,
            size="full",
            aspectRatio="20:13",
            aspectMode="cover",
        )

    return FlexBubble(
        size="kilo",
        hero=hero,
        header=FlexBox(
            layout="vertical",
            backgroundColor="#F7EFE5",
            paddingAll="16px",
            contents=[
                FlexText(text="W+ MEDIC", size="xs", color="#9A7138", weight="bold", align="center")
            ],
        ),
        body=FlexBox(layout="vertical", paddingAll="20px", contents=body_contents),
        footer=FlexBox(
            layout="vertical",
            paddingAll="16px",
            contents=[
                FlexButton(
                    style="primary",
                    color="#1F2937",
                    height="sm",
                    action=MessageAction(label="สอบถามแพ็กเกจนี้", text=action_text),
                )
            ],
        ),
    )


def _price(value) -> str | None:
    if value is None or value == "":
        return None
    try:
        amount = Decimal(str(value))
    except (InvalidOperation, ValueError):
        return None
    return f"฿{amount:,.0f}"


def _clip(value, limit: int) -> str:
    text = " ".join(str(value or "").split())
    return text if len(text) <= limit else f"{text[: limit - 1]}…"


def _is_https_url(value) -> bool:
    if not isinstance(value, str):
        return False
    parsed = urlparse(value)
    return parsed.scheme == "https" and bool(parsed.netloc)
