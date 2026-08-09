# Aion3 Knowledge Management

A low-cost, single-deploy **Laravel modular monolith** that manages business packages, FAQs,
and free-form knowledge entries. It exposes a small authenticated **read API** for the Python
AI orchestrator to consume.

The UI is a **lightweight internal admin tool** built with **Laravel + Inertia + React
(TypeScript)** — a single deploy, no separate SPA. There is no public/customer-facing site; the
only outward boundary is the authenticated compact **read API** consumed by `services/ai`.

- **Backend:** Laravel 13, PHP 8.3+
- **Frontend:** React 19 + TypeScript, Inertia 2, Tailwind CSS 4, Vite
- **Database:** MySQL (production). SQLite is used only for tests/dev convenience.
- **Excel:** maatwebsite/excel (XLSX import/export)
- **No Docker, no Redis, no vector DB, no separate SPA** — keep hosting cheap.

---

## 1. Requirements (Windows, non-Docker)

| Tool | Version | Notes |
|------|---------|-------|
| PHP | **8.3+** | required extensions below |
| Composer | 2.x | dependency install |
| Node.js | 20+ | with npm |
| MySQL | 8.0+ (or MariaDB 10.6+) | production database |

**Required PHP extensions:** `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`,
`json`, `bcmath`, `fileinfo`, `curl`, and **`zip` + `gd`** (needed by maatwebsite/excel / PhpSpreadsheet).

On Windows the simplest route is Laravel Herd, XAMPP (PHP 8.3 build), or the standalone PHP 8.3
zip. Enable the extensions above in `php.ini`.

---

## 2. First-time setup

```powershell
# From line-bot-management/
# inertiajs/inertia-laravel and maatwebsite/excel were added to composer.json after the
# committed lock file, so resolve them explicitly the first time:
composer update inertiajs/inertia-laravel maatwebsite/excel --with-all-dependencies
# (subsequent installs are just: composer install)

copy .env.example .env
php artisan key:generate
```

Then edit `.env`:

```dotenv
APP_NAME="Aion3 Knowledge Management"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=line_knowledge
DB_USERNAME=root
DB_PASSWORD=your-password

# Seed exactly ONE admin (never commit a real password)
ADMIN_NAME="Administrator"
ADMIN_EMAIL=admin@yourdomain.com
ADMIN_PASSWORD=choose-a-strong-password
```

Create the database, then migrate + seed:

```powershell
# In MySQL: CREATE DATABASE line_knowledge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
php artisan migrate --seed
```

The seeder creates only the admin from the `ADMIN_*` env values and skips safely when
`ADMIN_PASSWORD` is blank. Business records must be entered or imported by the business owner.

Build the frontend:

```powershell
npm install
npm run build
```

---

## 3. Development

```powershell
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite (HMR)
npm run dev
```

Visit `http://localhost:8000` for the public page and `http://localhost:8000/login` for the admin.

Front-end quality gates (no PHP needed):

```powershell
npm run typecheck   # tsc --noEmit
npm run lint        # eslint
npm run build       # production bundle
```

Back-end tests (needs PHP 8.3+; uses an in-memory SQLite DB, no MySQL required):

```powershell
php artisan test
```

---

## 4. Admin features

Session-authenticated admin area at `/admin` (login throttled: 5 attempts per email+IP, plus a
route-level throttle). Full CRUD with search / filter / pagination / delete-confirm / flash
messages for:

- **Package categories** and **Packages** — TH/EN names & descriptions, price, sale price, currency, duration, terms, keywords, active/published, effective date window
- **FAQs** — TH (and optional EN) question/answer, category, tags, active
- **Knowledge entries** — title, body (plaintext/Markdown), type, category, tags, source URL, version, reviewed_at, active
- **Answer analytics** — interaction metadata, daily/total counts, success rate, and response type without storing channel user IDs

---

## 5. XLSX import / export

คู่มือรูปแบบไฟล์ฉบับเต็มพร้อมชนิดข้อมูลและตัวอย่าง CSV: [`docs/IMPORT_FORMAT.md`](docs/IMPORT_FORMAT.md)

Under **นำเข้าแพ็กเกจ** (`/admin/imports`) for **packages and promotions only**:

- **Download template** → empty XLSX with the exact heading row.
- **Export** → back up current package/promotion data as XLSX.
- **Preview** → upload `.xlsx/.xls/.csv` (max 5 MB) and review new, duplicate, and invalid rows without writing to the database.
- **Confirm** → add new codes as unpublished drafts. Existing codes are skipped and never overwritten.
- FAQs and knowledge entries are managed through their web forms only.

**Column headings (snake_case, first row):**

| Resource | Columns |
|----------|---------|
| packages/promotions | `code, name_th, description_th, price, sale_price, effective_from, effective_until, terms, keywords` |

`code` and `name_th` are required. Dates use `YYYY-MM-DD`.

---

## 6. Knowledge read API (for the Python AI service)

Read-only, active-data-only JSON under `/api/v1`, guarded by a **revocable, hashed bearer token**
(only a SHA-256 hash is stored; the plaintext is shown once) and rate-limited (120 req/min).

### Issue / manage tokens

Via CLI:

```powershell
php artisan api-token:issue "chatwoot-ai-service" --expires=365
php artisan api-token:list
php artisan api-token:revoke 3
```

Or in the admin UI at `/admin/api-tokens` (the plaintext is shown once on creation).

### Endpoints

| Method | Path | Filters |
|--------|------|---------|
| GET | `/api/v1/meta` | active counts, `last_updated`, `schema_version` |
| GET | `/api/v1/packages` | `category`, `updated_since` (active **+ published + in-window** only) |
| GET | `/api/v1/faqs` | `category`, `updated_since` |
| GET | `/api/v1/knowledge` | `type`, `category`, `updated_since` |

All responses use a stable envelope:

```json
{ "meta": { "generated_at": "...", "count": 12, "version": "1.0" }, "data": [ ... ] }
```

Example:

```bash
curl -H "Authorization: Bearer lk_xxxx.yyyy" https://your-host/api/v1/packages?category=laser-skin
```

See `docs/ARCHITECTURE.md` for the module map and data contracts.

---

## 7. Security notes

- Never commit `.env`, tokens, or LINE/OpenRouter secrets. `.env.example` holds placeholders only.
- Admin passwords are seeded from env, hashed with bcrypt; API tokens are stored as SHA-256 hashes.
- The API returns only active records (packages also require published + effective window).
