from pydantic import AliasChoices, Field
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
        populate_by_name=True,
    )

    LINE_CHANNEL_SECRET: str
    LINE_CHANNEL_ACCESS_TOKEN: str
    OPENROUTER_API_KEY: str = Field(
        validation_alias=AliasChoices("OPENROUTER_API_KEY", "open_router_api")
    )
    # A pinned instruct model — never the "openrouter/free" auto-router, which
    # can route to reasoning/low-quality models that leak chain-of-thought.
    # Flash-lite: fast, natural Thai, clean (non-reasoning) output for LINE chat.
    OPENROUTER_MODEL: str = "google/gemini-3.1-flash-lite-preview"
    AI_TIMEOUT_SECONDS: float = Field(default=20.0)
    ANALYTICS_API_URL: str | None = None
    ANALYTICS_API_TOKEN: str | None = None
    ANALYTICS_TIMEOUT_SECONDS: float = Field(default=3.0)
    KNOWLEDGE_API_URL: str | None = None
    KNOWLEDGE_API_TOKEN: str | None = None
    KNOWLEDGE_TIMEOUT_SECONDS: float = Field(default=5.0)
    KNOWLEDGE_CACHE_SECONDS: float = Field(default=60.0)
