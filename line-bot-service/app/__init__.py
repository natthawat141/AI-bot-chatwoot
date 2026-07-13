import logging

from fastapi import FastAPI
from pydantic import ValidationError

from app.config import Settings
from app.routers import health
from app.routers.webhook import create_router as create_webhook_router
from app.services.ai import AIService
from app.services.analytics import AnalyticsClient
from app.services.knowledge_api import KnowledgeApiClient
from app.services.line_client import LineClient

logger = logging.getLogger("line_bot")


def setup_logging() -> None:
    logging.basicConfig(level=logging.INFO)


def _settings_from_env() -> Settings:
    try:
        return Settings()
    except ValidationError as exc:
        missing = [
            str(err["loc"][0])
            for err in exc.errors()
            if err["type"] == "missing"
        ]
        if missing:
            raise SystemExit(
                f"ขาด env vars: {', '.join(missing)} — ใส่ใน .env ตามตัวอย่างใน .env.example"
            ) from exc
        raise


def create_app(
    settings: Settings | None = None,
    ai_service: AIService | None = None,
    line_client: LineClient | None = None,
    analytics_client: AnalyticsClient | None = None,
    knowledge_client: KnowledgeApiClient | None = None,
) -> FastAPI:
    setup_logging()

    resolved_settings = settings or _settings_from_env()
    resolved_knowledge = knowledge_client or KnowledgeApiClient(resolved_settings)
    resolved_ai = ai_service or AIService(
        resolved_settings, knowledge_client=resolved_knowledge
    )
    resolved_line = line_client or LineClient(resolved_settings)
    resolved_analytics = analytics_client or AnalyticsClient(resolved_settings)

    app = FastAPI()
    app.include_router(health.router)
    app.include_router(
        create_webhook_router(
            resolved_settings,
            resolved_ai,
            resolved_line,
            resolved_analytics,
            resolved_knowledge,
        )
    )
    return app
