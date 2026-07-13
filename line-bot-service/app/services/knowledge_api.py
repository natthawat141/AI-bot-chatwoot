"""Read-only client for the line-bot-management knowledge API.

Fetches active packages, FAQs, and knowledge entries. The management side
guarantees only active records inside their effective window are returned,
so a snapshot is trustworthy for grounding as long as it is fresh. A short
TTL cache keeps webhook latency low; on transient failures a slightly stale
snapshot is still preferred over falling back to mock data.
"""

import json
import logging
from dataclasses import dataclass
from time import monotonic
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

from app.config import Settings

logger = logging.getLogger("line_bot.knowledge_api")

# Never serve a snapshot older than this, even when the API is down —
# expired packages must not leak into answers (shared product contract).
MAX_STALE_SECONDS = 1800.0

# After a failed refresh, skip re-fetching for this long so an outage does not
# add sequential HTTP timeouts to every incoming LINE message.
FAILURE_BACKOFF_SECONDS = 30.0


@dataclass(frozen=True)
class KnowledgeSnapshot:
    packages: tuple[dict, ...]
    faqs: tuple[dict, ...]
    entries: tuple[dict, ...]


EMPTY_SNAPSHOT = KnowledgeSnapshot(packages=(), faqs=(), entries=())


class KnowledgeApiClient:
    # The unsynchronized cache is intentional: attribute reads/writes are
    # atomic in CPython and the snapshot is frozen, so the worst case is a
    # duplicate fetch at TTL expiry — never a torn read.
    def __init__(self, settings: Settings):
        self._base_url = (settings.KNOWLEDGE_API_URL or "").rstrip("/")
        self._token = settings.KNOWLEDGE_API_TOKEN or ""
        self._timeout = settings.KNOWLEDGE_TIMEOUT_SECONDS
        self._cache_seconds = settings.KNOWLEDGE_CACHE_SECONDS
        self._cached: KnowledgeSnapshot | None = None
        self._cached_at: float = 0.0
        self._last_failure_at: float | None = None

    @property
    def enabled(self) -> bool:
        return bool(self._base_url and self._token)

    def fetch_snapshot(self) -> KnowledgeSnapshot | None:
        if not self.enabled:
            return None

        now = monotonic()
        if self._cached is not None and now - self._cached_at < self._cache_seconds:
            return self._cached

        if (
            self._last_failure_at is not None
            and now - self._last_failure_at < FAILURE_BACKOFF_SECONDS
        ):
            return self._stale_or_none()

        try:
            snapshot = KnowledgeSnapshot(
                packages=tuple(self._fetch("/api/v1/packages")),
                faqs=tuple(self._fetch("/api/v1/faqs")),
                entries=tuple(self._fetch("/api/v1/knowledge")),
            )
        except (HTTPError, URLError, TimeoutError, OSError, ValueError) as exc:
            logger.warning("Knowledge fetch failed: %s", type(exc).__name__)
            self._last_failure_at = monotonic()
            return self._stale_or_none()

        self._cached = snapshot
        self._cached_at = monotonic()
        self._last_failure_at = None
        return snapshot

    def _stale_or_none(self) -> KnowledgeSnapshot | None:
        if self._cached is not None and monotonic() - self._cached_at < MAX_STALE_SECONDS:
            return self._cached
        return None

    def _fetch(self, path: str) -> list[dict]:
        request = Request(
            f"{self._base_url}{path}",
            headers={
                "Authorization": f"Bearer {self._token}",
                "Accept": "application/json",
            },
        )
        with urlopen(request, timeout=self._timeout) as response:
            payload = json.load(response)

        data = payload.get("data") if isinstance(payload, dict) else None
        if not isinstance(data, list):
            raise ValueError("unexpected payload shape")
        return [row for row in data if isinstance(row, dict)]
