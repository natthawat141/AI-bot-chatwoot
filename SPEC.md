# Business Omnichannel AI — Version 1 Specification

## Document Status

- Version: 1.0
- Status: Product direction approved; Version 1 implementation deployed to the isolated GCP VM
  and verified through service/API health checks. Real channel credentials and owned production
  hostname remain deployment inputs, not source-controlled configuration.
- Date: 2026-08-08
- Product owner: User
- Tech lead: Codex
- Implementation: Codex, only after product-owner approval; `agy` is not used

## 1. Product Goal

Build a single-business AI assistant that serves customers through LINE and WhatsApp conversations unified in Chatwoot. Business staff manage catalog data and knowledge in Management. The AI answers from current structured records, can search inventory on demand, and hands the conversation to a Chatwoot team whenever human judgement is required.

The system must support general businesses rather than a language-school domain. Real estate is a required reference scenario, not a hard-coded vertical.

Example customer questions that Version 1 must support:

- “มีที่ดินแถวบางนาไหม”
- “มีคอนโดโครงการไหนบ้าง”
- “คอนโด 2 ห้องนอน งบไม่เกิน 4 ล้านบาทมีอะไรบ้าง”
- “แพ็กเกจที่ถูกที่สุดราคาเท่าไร”
- “โปรโมชันนี้ยังใช้ได้อยู่ไหม”
- “ขอคุยกับเจ้าหน้าที่”

## 2. Confirmed Product Decisions

1. Version 1 supports one business only. It is not a multi-tenant SaaS platform.
2. Human handoff routes to a Chatwoot team, not a specific individual.
3. Version 1 does not implement booking, appointments, calendar APIs, payment collection, or payment execution.
4. Management/MySQL owns business data. Chatwoot owns conversations.
5. Chatwoot is the only primary channel/conversation path for LINE and WhatsApp.
6. The AI queries business information through authenticated Management APIs and never connects directly to MySQL.
7. The Version 1 reference dataset represents a real-estate business that sells and rents condos, houses, land, and commercial properties. Reference data is synthetic and contains no real customer PII.
8. The LLM provider is OpenRouter and the configured model is `deepseek/deepseek-v4-flash-0731`.
9. `OPENROUTER_API_KEY` is supplied through the runtime environment only. Its value must never appear in source control, documentation, logs, container images, or test fixtures.
10. The deployment target is the existing GCP VM environment managed through Bangna Hos CLI. Exact project, zone, instance, network, and production identifiers remain deployment configuration and are not committed to this specification.

### Confirmed LLM Configuration

- Provider API: OpenRouter.
- Model: `deepseek/deepseek-v4-flash-0731`.
- Runtime variable: `OPENROUTER_API_KEY`.
- The model name is configuration, not hard-coded policy logic.
- No automatic fallback to another provider or model is allowed in Version 1 unless the product owner approves it.
- OpenRouter failures must follow the safe failure and human-handoff rules in this specification; they must never produce a guessed business answer.

### Confirmed Deployment Target

- Platform: Google Cloud Platform virtual machine.
- Environment: existing Bangna Hos CLI-managed VM environment.
- Packaging target: one reviewed Docker Compose deployment bundle for the VM.
- Production secrets are injected on the VM and never committed.
- Build, push, migration, and deployment require separate product-owner approval.

## 3. Users and Roles

### Customer

- Sends questions through LINE or WhatsApp.
- Receives grounded answers, catalog results, clarification questions, or a handoff acknowledgement.

### Human Agent

- Works in Chatwoot.
- Receives conversations routed to a configured team.
- Can respond without AI interference.
- Can explicitly return an eligible conversation to AI.

### Business Admin

- Uses Management to maintain catalog items, categories, prices, promotions, FAQs, policies, and knowledge entries.
- Publishes/unpublishes records and controls availability/effective dates.
- Does not edit prompts or deploy services to update normal business information.

### System Administrator

- Configures Chatwoot inbox/team IDs, API URLs, credentials, timeouts, and operational limits.
- Does not place secrets in source control.

## 4. System Ownership and Boundaries

| Concern | System of record | Allowed access |
|---|---|---|
| Conversations and messages | Chatwoot | Chatwoot public HTTP API and verified webhooks |
| Inbox, team assignment, status, `ai_mode` | Chatwoot | Chatwoot public HTTP API |
| Catalog, prices, availability, promotions | Management/MySQL | Authenticated Management API |
| FAQs, business policies, knowledge | Management/MySQL | Authenticated Management API |
| AI decisions, retrieval orchestration, dedup | AI orchestrator | In-process logic plus approved HTTP APIs |
| Secrets | Deployment secret store | Runtime injection only |

The legacy direct-LINE FastAPI implementation is not included. The Python service at `services/ai` receives Chatwoot events only and is the primary Version 1 AI conversation runtime.

## 5. Core Conversation Flow

1. LINE or WhatsApp delivers a customer message to Chatwoot.
2. Chatwoot creates/updates the conversation and sends a webhook to the AI orchestrator.
3. The orchestrator verifies webhook authenticity and reserves a stable deduplication key.
4. The orchestrator refetches live Chatwoot ownership and state.
5. A deterministic router chooses one action:
   - answer from FAQ/knowledge;
   - search the catalog;
   - ask one focused clarification question;
   - hand off to a human team;
   - ignore an ineligible event.
6. For catalog search, the orchestrator sends a validated structured query to Management.
7. The LLM receives only bounded, validated records and produces a grounded response.
8. Before sending, the orchestrator refetches ownership. If a human has taken over, it sends nothing.

## 6. Functional Requirements

### 6.1 Channel and Conversation Requirements

- **FR-CH-001:** LINE and WhatsApp conversations must appear in Chatwoot.
- **FR-CH-002:** The AI orchestrator must process verified Chatwoot webhook events only.
- **FR-CH-003:** Message deduplication must use a stable event identity that cannot be changed independently of the signed event content.
- **FR-CH-004:** Retries must not produce duplicate public replies.
- **FR-CH-005:** The AI must never compete with another direct LINE webhook on the primary path.

### 6.2 AI Eligibility and Ownership

- **FR-OWN-001:** AI may respond only when the configured inbox is allowed, the sender is the customer, `ai_mode` permits AI, and no human owns the conversation.
- **FR-OWN-002:** The orchestrator must refetch Chatwoot state before LLM work and immediately before a public reply.
- **FR-OWN-003:** Ownership lookup failure must fail closed and allow a safe retry.
- **FR-OWN-004:** A human-assigned, resolved, snoozed, or human-mode conversation must not receive an AI reply.
- **FR-OWN-005:** Return to AI must be explicit and auditable.

### 6.3 Management Knowledge

- **FR-KB-001:** Admins can create, edit, publish, unpublish, activate, and deactivate FAQs and knowledge entries.
- **FR-KB-002:** The read API returns active records only.
- **FR-KB-003:** Business policy and FAQ answers are versioned by `updated_at` and can be refreshed without redeploying the AI.
- **FR-KB-004:** API results use a stable versioned envelope and bounded pagination/limits.
- **FR-KB-005:** The orchestrator validates response schemas before adding records to an LLM prompt.

### 6.4 Domain-Neutral Catalog

The canonical concept is `Catalog Item`. Existing packages are one catalog item type; land and condos are other types.

- **FR-CAT-001:** Admins can manage catalog categories and catalog items.
- **FR-CAT-002:** Every catalog item has structured base fields:
  - `id`, `code`, `category_id`, `item_type`;
  - Thai name and optional English name;
  - Thai description and optional English description;
  - regular price, sale price, currency;
  - transaction type such as `sale`, `rent`, or `service` when applicable;
  - availability status;
  - searchable location text and optional province/district/subdistrict;
  - keywords/tags;
  - active, published, effective-from, effective-until;
  - created and updated timestamps.
- **FR-CAT-003:** Categories can define additional typed attributes with an allowlisted key, label, data type, unit, filter operators, and whether the attribute is searchable.
- **FR-CAT-004:** Catalog item attribute values are validated against their category definitions before save/import.
- **FR-CAT-005:** For real estate, supported category attributes include, when applicable:
  - project name;
  - bedrooms and bathrooms;
  - usable area in square metres;
  - land area in square wah;
  - floor;
  - property features.
- **FR-CAT-006:** Non-real-estate businesses may leave real-estate fields unused and define their own category attributes without changing orchestrator code.
- **FR-CAT-007:** Existing package records and `/api/v1/packages` remain usable during migration.

### 6.5 Catalog Search API

- **FR-SEARCH-001:** Management exposes an authenticated, read-only catalog search operation at `POST /api/v1/catalog/search`.
- **FR-SEARCH-002:** The request accepts only validated structured filters. Proposed contract:

```json
{
  "query": "คอนโดบางนา",
  "category_slug": "condo",
  "transaction_type": "sale",
  "location": {
    "province": "กรุงเทพมหานคร",
    "district": "บางนา",
    "text": "บางนา"
  },
  "price": { "min": null, "max": 4000000 },
  "attributes": {
    "bedrooms": { "gte": 2 }
  },
  "availability": ["available"],
  "sort": "relevance",
  "limit": 10,
  "cursor": null
}
```

- **FR-SEARCH-003:** The server rejects unknown attributes, operators, sort values, oversized text, invalid ranges, and limits above the configured maximum.
- **FR-SEARCH-004:** The search implementation must use server-owned query builders. No SQL or database field names come from the LLM.
- **FR-SEARCH-005:** Results include only active, published, effective, and available records.
- **FR-SEARCH-006:** The response returns bounded result summaries, applied filters, result count, and an opaque next cursor when more results exist.
- **FR-SEARCH-007:** Management exposes `GET /api/v1/catalog/{id}` for a permitted item detail lookup.
- **FR-SEARCH-008:** Search and detail endpoints require a revocable read-only bearer token and rate limiting.

### 6.6 AI Catalog Query Behaviour

- **FR-AI-001:** The AI distinguishes informational questions from catalog-search intent.
- **FR-AI-002:** It extracts only supported filters and validates them before calling Management.
- **FR-AI-003:** If a missing detail materially changes results, it asks one concise clarification question. Example: sale versus rent.
- **FR-AI-004:** It may answer broad discovery questions such as “มีที่ดินที่ไหนบ้าง” with bounded grouped results and a follow-up filter question.
- **FR-AI-005:** It must not claim that an item exists, is available, or has a price unless the API returned that fact.
- **FR-AI-006:** Zero exact matches must be reported honestly. Filters may be relaxed only after telling the customer and receiving consent or presenting the relaxation explicitly.
- **FR-AI-007:** Results must show enough identity to continue the conversation: item name/code, location, relevant attributes, price/status, and a safe next action.
- **FR-AI-008:** The orchestrator must not load the entire catalog into the prompt. Default result limit is 10; the maximum is 20.
- **FR-AI-009:** Search filters and returned facts are treated as data, not instructions to the model.

### 6.7 Human Handoff

- **FR-HO-001:** Handoff triggers include explicit human requests, complaints, unsupported negotiation, payment/refund questions, low-confidence or invalid AI output, and unavailable required data.
- **FR-HO-002:** Handoff first sets `ai_mode=human` and moves the conversation to the configured human state.
- **FR-HO-003:** The conversation is assigned to a configured Chatwoot team, not a hard-coded agent.
- **FR-HO-004:** A neutral public acknowledgement is sent only after the AI lock succeeds.
- **FR-HO-005:** Private notes use deterministic templates and must not include raw LLM reasoning.
- **FR-HO-006:** Return to AI clears incompatible human ownership, sets the configured AI state, and records the action.

### 6.8 Failure Behaviour

- **FR-FAIL-001:** A short Management API timeout is required and must be bounded by the overall response deadline.
- **FR-FAIL-002:** Cached knowledge may be used within a documented stale-if-error window.
- **FR-FAIL-003:** Availability and price-sensitive results must identify stale data internally and must not be presented as confirmed-current beyond the allowed stale window.
- **FR-FAIL-004:** If no safe source exists, the AI says it cannot confirm and offers handoff; it never guesses.
- **FR-FAIL-005:** AI transport failure returns a retryable failure without locking the customer into human mode unless deterministic policy independently requires handoff.

## 7. State Model

### AI Active

- `ai_mode=ai`
- configured AI-eligible Chatwoot status
- no individual human assignee
- configured inbox is allowed

### Human Active

- `ai_mode=human`
- configured human status, normally `open`
- assigned to the configured team
- AI ignores new customer messages until explicit return

### Transition Rules

- AI Active → Human Active: deterministic handoff or validated AI handoff action.
- Human Active → AI Active: explicit staff/admin action only.
- Any ambiguous or failed transition: remain/fail closed in the safer human-owned state.

## 8. Non-Functional Requirements

### Security and Privacy

- **NFR-SEC-001:** No secrets or production identifiers in source control.
- **NFR-SEC-002:** No customer content, PII, raw prompts, raw LLM output, or private notes in application logs.
- **NFR-SEC-003:** API tokens are hashed at rest, revocable, expirable, and scoped to required abilities.
- **NFR-SEC-004:** Webhook verification and replay protection happen before LLM or upstream API work.
- **NFR-SEC-005:** All LLM-facing text is length-bounded and separated from system instructions.

### Reliability

- **NFR-REL-001:** Duplicate delivery must produce at most one public reply.
- **NFR-REL-002:** Knowledge/catalog API failures degrade to bounded cache or safe handoff.
- **NFR-REL-003:** Ownership races fail closed.
- **NFR-REL-004:** Migrations are additive and reversible before destructive cleanup is considered.

### Observability

- **NFR-OBS-001:** Allowed metadata includes event type, delivery ID, conversation/account IDs, action, status, duration, error class/code, and reason category.
- **NFR-OBS-002:** Metrics distinguish answer, clarification, catalog search, zero result, handoff, ignored event, retryable failure, and upstream timeout.
- **NFR-OBS-003:** Logs and metrics must not contain message bodies or catalog records with sensitive fields.

## 9. Version 1 Exclusions

- Multi-business tenant isolation, tenant billing, and tenant provisioning.
- Booking, appointment scheduling, calendar integration, or availability reservation.
- Payment processing, payment links generated by AI, refunds, or financial transactions.
- Autonomous catalog modification by AI.
- Direct SQL generated by or exposed to the LLM.
- Vector database or semantic retrieval infrastructure unless separately approved after measured need.
- Public customer storefront, CRM replacement, or Chatwoot replacement.
- Teacher/student/language-school workflows.

## 10. Acceptance Criteria

- **AC-001:** A LINE or WhatsApp customer message reaches Chatwoot and triggers at most one eligible AI processing attempt.
- **AC-002:** Updating an active FAQ or catalog item in Management affects AI answers after the configured cache TTL without an AI redeploy.
- **AC-003:** Unpublished, inactive, expired, or unavailable catalog items never appear as available results.
- **AC-004:** “มีที่ดินที่ไหนบ้าง” returns bounded results sourced from Catalog Search API or honestly reports no match.
- **AC-005:** “คอนโด 2 ห้องนอน งบไม่เกิน 4 ล้านบาท” produces validated category, price, and bedroom filters and returns only matching API records.
- **AC-006:** Unknown or malicious attribute/filter input cannot become arbitrary SQL or an unrestricted database query.
- **AC-007:** Zero exact results do not cause invented listings or silently relaxed filters.
- **AC-008:** An explicit request for a human locks AI and routes the conversation to the configured Chatwoot team.
- **AC-009:** After human takeover, concurrent or later webhook retries do not send an AI public reply.
- **AC-010:** Return to AI requires an explicit action and restores only the configured AI state.
- **AC-011:** Management API outage uses permitted cache or gives a safe cannot-confirm/handoff response.
- **AC-012:** No active runtime message or decision assumes a teacher, student, lesson, or language school.
- **AC-013:** Relevant management backend tests, frontend typecheck/lint/build, and orchestrator tests/typecheck/build pass where repository rules permit execution.
- **AC-014:** Delivery documentation lists all configuration, migrations, verification evidence, known limitations, and production steps not executed.

## 11. Implementation Sequence

Codex must implement only one product-owner-approved slice at a time. No work is delegated to `agy`:

1. **Catalog contract:** schema design, category attribute definitions, migrations, model validation, and API contract tests.
2. **Management catalog UI:** CRUD, filters, publish/availability controls, and import/export updates.
3. **Catalog Search API:** validated filters, detail lookup, limits, auth, and rate limiting.
4. **AI catalog tool:** intent routing, filter extraction/validation, API client, clarification, zero-result behavior, and bounded presentation.
5. **Generic handoff:** team-based routing, deterministic messages, explicit Return to AI, and race tests.
6. **End-to-end verification:** LINE/WhatsApp through Chatwoot using non-production fixtures and no real customer data.

Each slice requires tech-lead review before the next slice begins.

## 12. Production Readiness Gate

Passing unit tests is not proof of production readiness. Production enablement additionally requires:

- a reviewed Management database migration and rollback plan;
- a real read-only API token stored outside source control;
- verified private network connectivity between the orchestrator and Management;
- configured Chatwoot inbox and team IDs;
- webhook authenticity and retry tests;
- representative catalog data quality review;
- log privacy review;
- controlled rollout with a rollback path.
