from unittest.mock import MagicMock, patch

from app.services.analytics import AnalyticsClient


def test_analytics_client_hashes_user_and_posts_without_raw_line_id(settings):
    settings.ANALYTICS_API_URL = "http://127.0.0.1:8001"
    settings.ANALYTICS_API_TOKEN = "test-token"
    client = AnalyticsClient(settings)
    response = MagicMock()
    response.status = 201
    response.__enter__.return_value = response

    with patch("app.services.analytics.urlopen", return_value=response) as mocked_urlopen:
        assert client.record_interaction(
            event_id="evt-1",
            message_id="msg-1",
            user_id="U-secret-user",
            question="สวัสดี",
            answer="สวัสดีครับ",
            response_type="ai",
            status="answered",
            model="test-model",
            duration_ms=100,
        )

    body = mocked_urlopen.call_args.args[0].data.decode("utf-8")
    assert "U-secret-user" not in body
    assert '"user_hash":' in body
