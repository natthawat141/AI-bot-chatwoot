# LINE Bot Service

FastAPI service for the first working path:

`LINE webhook -> OpenRouter -> LINE reply`

Business knowledge is intentionally not copied from the old project. The
boundary is `app/services/knowledge.py`. When `KNOWLEDGE_API_URL` and
`KNOWLEDGE_API_TOKEN` are set, `app/services/knowledge_api.py` retrieves
active packages, FAQs, and knowledge entries from `line-bot-management`
(`GET /api/v1/packages|faqs|knowledge`, cached for `KNOWLEDGE_CACHE_SECONDS`)
and grounds AI answers on them. When unset or unreachable, the bot falls back
to the built-in mock knowledge with a mock-data disclaimer.

Issue the API token on the management side (grants both knowledge read and
analytics write):

```powershell
cd ..\line-bot-management
php artisan api-token:issue line-bot-service --ability=read --ability=analytics:write
```

Put the printed plaintext token in `.env` as both `KNOWLEDGE_API_TOKEN` and
`ANALYTICS_API_TOKEN`. In Docker, use `http://host.docker.internal:8001`
instead of `http://127.0.0.1:8001` for both URLs.

## Local setup

```powershell
py -3.12 -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements.txt -r requirements-dev.txt
Copy-Item .env.example .env
```

Fill the three secret values in `.env`, then run:

```powershell
.\.venv\Scripts\python.exe -m uvicorn main:app --reload --port 8000
```

Endpoints:

- `GET /health`
- `POST /webhook`

Run tests:

```powershell
.\.venv\Scripts\python.exe -m pytest -q
```

## Documentation

- [คู่มือตั้งค่า LINE Official Account และให้ AI/Codex ช่วยจัดการ](docs/LINE_OA_AI_SETUP_GUIDE_TH.md)
- [คู่มือทำ LINE Flex Message Carousel](docs/FLEX_CAROUSEL_GUIDE_TH.md)
