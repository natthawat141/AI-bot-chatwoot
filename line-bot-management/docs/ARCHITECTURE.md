# Architecture & Status

## Overview

`line-bot-management` is the CMS + read-API half of the LINE AI package-information product.
It is a **single-deploy Laravel modular monolith**: Laravel serves both the Inertia/React admin
UI and a small JSON API. No separate SPA, no Docker, no Redis, no vector DB.

```
Admin (browser) ──▶ Laravel (Inertia + React/TS)         ← internal knowledge admin
                    Laravel (/api/v1, hashed bearer)      ← consumed by line-bot-service
                    MySQL                                  ← single source of truth
```

There is **no public/customer-facing site**. React is purely a lightweight admin UI for managing
AI knowledge. The only outward boundary is the compact read API.

`line-bot-service` (FastAPI, separate project — **not touched here**) will call `/api/v1/*` to
ground its answers. Retrieval stays deterministic: structured columns are the source of truth.

## Why these choices

- **Inertia + React (not Filament):** the brief requires a React/TypeScript admin. Filament would
  duplicate that UI, so it is intentionally avoided.
- **Inertia (not a standalone SPA/API):** one session-authenticated deploy keeps hosting cheap and
  avoids CORS/token plumbing for the admin.
- **No Ziggy:** route URLs are centralised in `resources/js/lib/routes.ts` to keep the toolchain small.
- **Hashed API tokens (not Sanctum):** a tiny purpose-built `api_tokens` table (SHA-256 hash,
  one-time plaintext, revoke + expiry) is enough for one machine-to-machine consumer.

## Module map

| Area | Key files |
|------|-----------|
| Auth | `Http/Controllers/Auth/AuthenticatedSessionController`, `Http/Requests/Auth/LoginRequest` (throttle) |
| Admin CRUD | `Http/Controllers/Admin/*Controller`, `Http/Requests/*Request`, `Http/Resources/*Resource`, `Policies/*Policy` |
| Models | `Models/{PackageCategory,ServicePackage,Faq,KnowledgeEntry,BotInteraction,ApiToken,ImportRecord}` |
| API | `routes/api.php`, `Http/Controllers/Api/KnowledgeApiController`, `Http/Middleware/AuthenticateApiToken` |
| Tokens (CLI) | `Console/Commands/ApiToken{Issue,Revoke,List}Command` |
| Excel | `Imports/*Import`, `Exports/*Export`, `Http/Controllers/Admin/ImportExportController` |
| React | `resources/js/app.tsx`, `resources/js/pages/**`, `resources/js/components/**`, `resources/js/lib/**` |
| Inertia glue | `Http/Middleware/HandleInertiaRequests`, `resources/views/app.blade.php` |

## Data contracts (shared with the bot)

1. Package facts (code, price, status, effective dates) are **structured columns**.
2. Descriptions, benefits, conditions, FAQ answers are free-form `TEXT`.
3. The API returns **only active** records; packages additionally require `is_published` **and**
   the current date within `[effective_from, effective_until]` (nulls = open-ended).
4. Model scopes enforce this: `active()`, `published()`, `effective()`.

## Resource / pagination shape

`JsonResource::withoutWrapping()` is enabled, so single resources serialize flat. Paginated
collections still expose `{ data, meta: { current_page, last_page, per_page, total, from, to, links } }`,
which the React `Pagination` component and the `Paginated<T>` TS type consume.

## Status

**Done:** schema + migrations + factories, session auth with throttling + policies, full React/TS
CRUD for all five entities (+ package categories), XLSX import/export with per-row validation and
history, hashed bearer-token API with CLI + admin UI, W+ Medic verified seed data, and a PHP test
suite (feature + unit).

**Validation performed here:** Node/TypeScript only (`npm install`, `typecheck`, `lint`, `build`) —
see the delivery summary. **PHP/Composer were unavailable in the build environment**, so
`composer install`, `php artisan migrate`, and `php artisan test` must be run on a PHP 8.3+ host.
Composer dependencies are declared in `composer.json` but were not installed here.

**Deliberately out of scope:** any change to `line-bot-service` / `standard-line-bot`, and any
`.env`/secret handling beyond `.env.example` placeholders.
