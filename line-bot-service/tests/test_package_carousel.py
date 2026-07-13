from app.services.package_carousel import (
    MAX_CAROUSEL_ITEMS,
    build_package_carousel,
    carousel_kind,
    select_packages,
)


def test_carousel_kind_matches_rich_menu_messages():
    assert carousel_kind("ดูบริการและแพ็กเกจ") == "packages"
    assert carousel_kind(" ดูโปรโมชัน ") == "promotions"
    assert carousel_kind("ช่วยแนะนำบริการ") is None


def test_carousel_is_limited_to_line_maximum():
    packages = tuple(
        {"code": f"PKG-{index}", "name_th": f"แพ็กเกจ {index}"}
        for index in range(20)
    )
    selected = select_packages(packages, "packages")
    message = build_package_carousel(selected, "packages")

    assert len(selected) == MAX_CAROUSEL_ITEMS
    assert len(message.contents.contents) == MAX_CAROUSEL_ITEMS


def test_card_button_sends_package_code_back_to_bot():
    message = build_package_carousel(
        [
            {
                "code": "LASER-001",
                "name_th": "เลเซอร์หน้าใส",
                "price": 1500,
                "sale_price": 990,
            }
        ],
        "packages",
    )
    bubble = message.contents.contents[0]

    assert bubble.footer.contents[0].action.text == "สนใจแพ็กเกจ LASER-001"
    assert bubble.body.contents[-1].text == "฿990"
