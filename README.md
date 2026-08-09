# AI-bot-chatwoot

Monorepo สำหรับระบบ AI สนทนาธุรกิจเดียวผ่าน LINE และ WhatsApp โดยใช้ Chatwoot เป็นเจ้าของบทสนทนา และ Laravel Management เป็นเจ้าของข้อมูลธุรกิจ

## โครงสร้าง

```text
apps/management/       Laravel Management และ Knowledge API
services/ai/           Python AI orchestrator สำหรับ Chatwoot
infra/chatwoot/        Chatwoot web/worker, PostgreSQL และ Redis
AGENTS.md               กติกาการพัฒนา
SPEC.md                 สเปก Version 1
```

สิ่งที่ไม่นำมาจาก Starter Edition คือ direct LINE webhook เดิม, `vendor`, `node_modules`, build output, rich-menu assets และชุด deploy/observability เก่า

## รันทั้งระบบด้วย Docker Compose

```bash
cp .env.example .env
# ใส่ค่าลับ/โดเมนตามสภาพแวดล้อม และเก็บไฟล์นี้ไว้นอก git
docker compose up -d
```

Compose จะเริ่ม Caddy, Chatwoot Rails/Sidekiq, PostgreSQL, Redis, Laravel Management และ AI orchestrator พร้อมกัน
โดย `chatwoot-bootstrap` จะสร้าง account, ทีม handoff, Agent Bot และ service API token แบบ idempotent

สำหรับ VM ใหม่ใช้ `infra/deploy/bootstrap-vm.sh` เพื่อสร้าง `.env` และรัน migration/seed อัตโนมัติ
ค่า OpenRouter ควรฉีดผ่าน secret manager ลง `runtime/openrouter.env` (mode 600) ไม่เขียนลง source

## Laravel Management

```bash
cd apps/management
composer install
npm install
php artisan test
npm run build
```

## Python AI service

```bash
cd services/ai
python3 -m venv .venv
source .venv/bin/activate
pip install -e '.[dev]'
pytest
uvicorn ai_service.main:app --reload
```

ก่อนเปิด channel จริง ให้ตั้ง `LINE_CHANNEL_ID`, `LINE_CHANNEL_SECRET` และ `LINE_CHANNEL_ACCESS_TOKEN`
ใน runtime environment แล้วรัน `docker compose up -d chatwoot-bootstrap ai` อีกครั้ง
จากนั้นจึงนำ Chatwoot inbox webhook URL ไปตั้งใน LINE Developers หรือช่องทางที่เลือก
