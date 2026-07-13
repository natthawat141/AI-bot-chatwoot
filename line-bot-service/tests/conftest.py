import os
from unittest.mock import MagicMock

import pytest
from fastapi.testclient import TestClient

# ตั้ง env ปลอมก่อน import app — ไม่ต้องมี .env จริง
os.environ.setdefault("LINE_CHANNEL_SECRET", "test-channel-secret")
os.environ.setdefault("LINE_CHANNEL_ACCESS_TOKEN", "test-access-token")
os.environ.setdefault("OPENROUTER_API_KEY", "test-openrouter-key")

from app import create_app
from app.config import Settings
from app.services.ai import AIService
from app.services.analytics import AnalyticsClient
from app.services.knowledge_api import EMPTY_SNAPSHOT, KnowledgeApiClient
from app.services.line_client import LineClient


@pytest.fixture
def settings() -> Settings:
    return Settings(
        LINE_CHANNEL_SECRET="test-channel-secret",
        LINE_CHANNEL_ACCESS_TOKEN="test-access-token",
        OPENROUTER_API_KEY="test-openrouter-key",
        OPENROUTER_MODEL="google/gemma-4-31b-it:free",
        AI_TIMEOUT_SECONDS=5.0,
    )


@pytest.fixture
def mock_line_client():
    return MagicMock(spec=LineClient)


@pytest.fixture
def mock_ai_service():
    service = MagicMock(spec=AIService)
    service.get_reply.return_value = "คำตอบจาก AI"
    return service


@pytest.fixture
def mock_analytics_client():
    client = MagicMock(spec=AnalyticsClient)
    client.record_interaction.return_value = True
    return client


@pytest.fixture
def mock_knowledge_client():
    client = MagicMock(spec=KnowledgeApiClient)
    client.fetch_snapshot.return_value = EMPTY_SNAPSHOT
    return client


@pytest.fixture
def app(
    settings,
    mock_ai_service,
    mock_line_client,
    mock_analytics_client,
    mock_knowledge_client,
):
    return create_app(
        settings=settings,
        ai_service=mock_ai_service,
        line_client=mock_line_client,
        analytics_client=mock_analytics_client,
        knowledge_client=mock_knowledge_client,
    )


@pytest.fixture
def client(app):
    return TestClient(app)
