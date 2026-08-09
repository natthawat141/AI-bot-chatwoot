# Management Architecture

## Responsibility

`apps/management` is the system of record for business data. Laravel serves the internal Inertia/React admin UI and authenticated JSON APIs from one application.

```text
Business admin -> Laravel/Inertia -> MySQL
Python AI service -> authenticated /api/v1 -> Laravel -> MySQL
```

Chatwoot owns conversations and human assignment. The Python service owns orchestration. Neither service connects directly to the Management database.

## Modules

| Area | Key files |
|---|---|
| Authentication | `app/Http/Controllers/Auth`, `app/Http/Requests/Auth` |
| Admin CRUD | `app/Http/Controllers/Admin`, `app/Http/Requests`, `app/Policies` |
| Business models | `app/Models` |
| Read API | `routes/api.php`, `app/Http/Controllers/Api/KnowledgeApiController.php` |
| API tokens | `app/Console/Commands/ApiToken*`, `app/Http/Middleware/AuthenticateApiToken.php` |
| Import/export | `app/Imports`, `app/Exports` |
| Admin UI | `resources/js` |

## Data contract

- Structured records are the source of truth for identity, price, status, publication, and effective dates.
- Read APIs return active records only; packages must also be published and inside their effective window.
- Machine clients use revocable hashed bearer tokens.
- The Version 1 catalog migration and structured search endpoint are governed by the root `SPEC.md`.

No clinic, school, booking, payment, or direct-LINE sample data is seeded.
