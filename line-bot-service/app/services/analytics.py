import hashlib
import json
import logging
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

from app.config import Settings

logger = logging.getLogger("line_bot.analytics")


class AnalyticsClient:
    """Send a compact, privacy-conscious answer log to AI Knowledge."""

    def __init__(self, settings: Settings):
        self._base_url = (settings.ANALYTICS_API_URL or "").rstrip("/")
        self._token = settings.ANALYTICS_API_TOKEN or ""
        self._timeout = settings.ANALYTICS_TIMEOUT_SECONDS

    @property
    def enabled(self) -> bool:
        return bool(self._base_url and self._token)

    def record_interaction(
        self,
        *,
        event_id: str,
        message_id: str | None,
        user_id: str | None,
        question: str,
        answer: str,
        response_type: str,
        status: str,
        model: str | None,
        duration_ms: int,
    ) -> bool:
        if not self.enabled:
            return False

        payload = {
            "event_id": event_id,
            "message_id": message_id,
            "user_hash": self._hash_user(user_id),
            "question": question,
            "answer": answer,
            "response_type": response_type,
            "status": status,
            "model": model,
            "duration_ms": duration_ms,
        }
        request = Request(
            f"{self._base_url}/api/v1/interactions",
            data=json.dumps(payload, ensure_ascii=False).encode("utf-8"),
            headers={
                "Authorization": f"Bearer {self._token}",
                "Content-Type": "application/json",
                "Accept": "application/json",
            },
            method="POST",
        )

        try:
            with urlopen(request, timeout=self._timeout) as response:
                return response.status in (200, 201)
        except (HTTPError, URLError, TimeoutError, OSError) as exc:
            logger.warning("Analytics delivery failed: %s", type(exc).__name__)
            return False

    @staticmethod
    def _hash_user(user_id: str | None) -> str | None:
        if not user_id:
            return None
        return hashlib.sha256(user_id.encode("utf-8")).hexdigest()
