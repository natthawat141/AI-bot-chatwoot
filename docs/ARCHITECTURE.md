# AI Bot Chatwoot Architecture

## Scope

Version 1 serves one business. Laravel Management is the system of record for business
knowledge and catalog data. Chatwoot is the system of record for conversations, inboxes, and
human ownership. The Python service orchestrates AI replies and never connects directly to a
Management database.

## Runtime architecture

```mermaid
flowchart TD
    subgraph linePlatform["LINE Platform & Client"]
        customer(["👤 Customer Mobile App"])
        richmenu["🖼️ LINE Rich Menu\n(6 Actions: Condo, House, Consign, Loan, Hours, Staff)"]
        linemessaging["📡 LINE Messaging API\n(Webhook & Push API)"]
    end

    caddy["🔒 Caddy HTTPS Reverse Proxy\n(SSL Termination & Routing)"]

    subgraph chatwoot["Chatwoot Conversation Platform"]
        rails["Chatwoot Rails API\nConversation & Inbox Owner"]
        sidekiq["Chatwoot Sidekiq\nAsync Delivery Jobs"]
        cwdb[("PostgreSQL\nChatwoot DB")]
        cwredis[("Redis\nChatwoot Cache")]
        inbox["LINE Business Inbox #1\nAgent Bot Webhook"]
    end

    subgraph ai["AI Orchestration Boundary"]
        webhook["AI Webhook API\nFastAPI"]
        queue[("Redis Durable Queue\nEvents")]
        worker["AI Worker\nSingle-process Locking & Routing"]
        openrouter["🧠 OpenRouter\nLLM Completion"]
    end

    subgraph management["Laravel Management (System of Record)"]
        react["Admin Dashboard\nReact + Inertia UI"]
        laravel["Laravel API v1\nCatalog, Knowledge, Flex Generator"]
        mdb[("MySQL\nManagement DB")]
    end

    %% Inbound Flow
    customer -->|Tap Menu Button / Type Chat| richmenu
    richmenu -->|Message Event| linemessaging
    linemessaging -->|Webhook Event| caddy
    caddy -->|/webhooks/line| rails
    rails --> inbox
    rails <--> sidekiq
    rails <--> cwdb
    sidekiq <--> cwredis

    %% AI Pipeline
    inbox -->|Webhook POST /webhook/chatwoot/{token}| webhook
    webhook -->|rpush event| queue
    queue -->|consume| worker

    %% Management API & Knowledge Integration
    worker -->|1. Search Catalog / FAQs / Business Profile| laravel
    worker -->|2. Fetch Structured LINE Flex JSON| laravel
    laravel <--> mdb
    react -->|Staff CRUD / Admin Profile| laravel

    %% LLM Grounding
    worker -->|3. Grounded Thai Chat Completion| openrouter

    %% Outbound Multi-Channel Delivery
    worker -->|4a. Deliver Conversational Text Message| rails
    rails -->|Send Message| linemessaging
    worker -->|4b. Direct LINE Push API: Flex Carousel & Cards| linemessaging
    linemessaging -->|Deliver Rich Cards & Chat| customer

    %% Staff Handoff & Return
    worker -->|Assign Team & Set Labels (human-handling)| rails
    rails -.->|Return to AI Label Event| webhook
```

## Message Lifecycle & Data Flow

1. **Customer Interaction via LINE:**
   * A customer taps a button on the **LINE Rich Menu** (e.g. 🏢 คอนโด, 🏡 บ้าน, 💰 สินเชื่อ, 📝 ฝากขาย, 🕒 ข้อมูล) or types a free-text message.
   * LINE delivers the message event through the **LINE Messaging API** to Caddy and into **Chatwoot's LINE Inbox**.

2. **Event Queueing & Orchestration:**
   * Chatwoot fires an `Agent Bot` webhook event to the **AI Webhook API (`FastAPI`)**.
   * The webhook validates the token and enqueues the payload into **Redis**.
   * The **AI Worker** consumes the event under a per-conversation asyncio lock to guarantee sequential processing.

3. **Data Retrieval from Laravel Management (System of Record):**
   * The AI worker queries the **Laravel Management API (`/api/v1`)**:
     * `POST /api/v1/catalog/search`: Retrieves real available condo/house listings with attribute filters.
     * `GET /api/v1/business-profile`: Retrieves authoritative business hours, contact info, and company metadata.
     * `GET /api/v1/faqs` & `GET /api/v1/knowledge`: Retrieves verified business policies and Q&As.
     * `GET /api/v1/flex/carousel` & `GET /api/v1/flex/{loan|consignment|about}`: Returns structured **LINE Flex Message JSON**.

4. **Response Delivery (Hybrid Text + Flex Cards):**
   * **Structured UI (Flex Cards):** For catalog listings and official services, the AI Worker pushes official **LINE Flex Carousel & Bubble Cards** directly via the **LINE Messaging Push API**.
   * **Conversational AI Text:** The AI Worker invokes **OpenRouter LLM** with grounded context to synthesize a natural, polite Thai chat response and sends it through the **Chatwoot Messages API**.

5. **Human Handoff & Return to AI:**
   * If the customer requests human assistance or mentions complaints/payment issues, the worker assigns the conversation to the staff team and applies the `human-handling` label.
   * When staff apply the `return-to-ai` label in Chatwoot, the worker resets `ai_mode` to `ai`, unassigns staff, and resumes automated AI responses.

## Ownership and security boundaries

| Concern | Owner | Boundary |
|---|---|---|
| Conversation history and inbox state | Chatwoot | Rails API and PostgreSQL |
| Business catalog and knowledge | Laravel Management | Authenticated read API and MySQL |
| AI orchestration and retries | Python AI service | FastAPI, Redis queue, worker |
| Model completion | OpenRouter | Outbound HTTPS from AI service |
| Human handoff | Chatwoot team | Team assignment; no individual agent binding |

Secrets are supplied through runtime environment files and are excluded from Git. Customer
messages are not written to application logs.

## Deployment topology

All services run in one Docker Compose stack on the development VM. Caddy is the only public
entry point. Chatwoot and Management keep their public hostnames, while the AI webhook has a
separate public HTTPS hostname because Chatwoot rejects private Compose hostnames for Agent Bot
webhooks. Caddy forwards that hostname privately to the AI API; the webhook still requires the
secret path token. Chatwoot, Management, AI API, and the AI Worker communicate over the private
Compose network. Persistent state is held in named volumes for Chatwoot PostgreSQL/Redis,
Management MySQL, and Chatwoot storage.
