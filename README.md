# LINE AI Package-Info Bot

LINE Official Account bot that answers customer questions from live,
admin-managed data — packages, prices, promotions, and FAQs — grounded on
real records, never guessed.

## What it does

A customer messages the LINE Official Account. The bot answers in natural
Thai, grounded only on active, published, in-window records managed by a
non-technical admin through a web UI. No supported record → the bot says so
and points to a human contact, instead of making something up.

```
LINE user -> LINE Messaging API -> line-bot-service (FastAPI)
                                        |  webhook, signature check, dedup
                                        |  OpenRouter (LLM) for natural replies
                                        v
                                 line-bot-management (Laravel/Filament)
                                        |  admin CRUD, Excel import/export
                                        |  read-only Knowledge API (bearer token)
                                        v
                                      MySQL
```

## Repository layout

| Path | What |
|---|---|
| `line-bot-service/` | FastAPI bot — LINE webhook, OpenRouter, knowledge grounding, Flex messages |
| `line-bot-management/` | Laravel/Filament — admin CRUD, Excel import/export, Knowledge read API, analytics sink |
| `deploy/` | nginx config, observability stack config, VM bootstrap script |
| `docker-compose.yml` | Local demo stack (SQLite, single command) |
| `docker-compose.prod.yml` | Production stack (MySQL, nginx, monitoring) |

## Quick start (local demo)

```bash
cp line-bot-service/.env.example line-bot-service/.env               # fill LINE + OpenRouter secrets
cp line-bot-service/.env.management.example line-bot-service/.env.management
docker compose up -d --build
```

- Bot health: `http://localhost:8080/health`
- Admin: `http://localhost:8001`

## Production deploy

See [`deploy/README.md`](deploy/README.md) for the full single-VM playbook
(provision → secrets → `docker compose -f docker-compose.prod.yml up -d --build`
→ point the LINE webhook at your domain).

## Documentation

- [Architecture & status](line-bot-management/docs/ARCHITECTURE.md)
- [Product brief](line-bot-management/PRODUCT.md)
- [LINE OA setup + AI-assisted management guide (TH)](line-bot-service/docs/LINE_OA_AI_SETUP_GUIDE_TH.md)
- [Flex Message carousel guide (TH)](line-bot-service/docs/FLEX_CAROUSEL_GUIDE_TH.md)

## License

Proprietary — see [LICENSE](LICENSE). Visible for demonstration purposes only.
