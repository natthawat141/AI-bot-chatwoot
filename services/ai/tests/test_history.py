from ai_service.history import build_history


def test_history_filters_private_activity_and_empty_messages() -> None:
    result = build_history([
        {"id": 1, "created_at": 100, "message_type": 0, "content": "มีคอนโดไหม"},
        {"id": 2, "created_at": 101, "message_type": 2, "content": "activity"},
        {"id": 3, "created_at": 102, "message_type": 1, "private": True, "content": "internal"},
        {"id": 4, "created_at": 103, "message_type": 1, "content": ""},
        {"id": 5, "created_at": 104, "message_type": 1, "content": "มีครับ"},
    ])

    assert result == [
        {"role": "user", "content": "มีคอนโดไหม"},
        {"role": "assistant", "content": "มีครับ"},
    ]


def test_history_is_bounded_and_chronological() -> None:
    messages = [
        {"id": index, "created_at": index, "message_type": 0, "content": f"message-{index}"}
        for index in range(1, 15)
    ]

    result = build_history(messages, max_messages=3, max_chars=30)

    assert len(result) == 3
    assert result == [
        {"role": "user", "content": "message-12"},
        {"role": "user", "content": "message-13"},
        {"role": "user", "content": "message-14"},
    ]
