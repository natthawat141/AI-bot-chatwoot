# Python AI Service

The service exposes a FastAPI webhook endpoint and a separate Redis-backed worker. The webhook
acknowledges authenticated Chatwoot events only after they are durably queued; the worker then
performs Chatwoot and Management API calls with bounded retries.

โครง FastAPI สำหรับรับ event จาก Chatwoot และประสานงานกับ Management API

ความสามารถใน Version 1:

- `GET /health` ใช้ตรวจ process health
- `POST /webhooks/chatwoot/{secret-path}` ตรวจ token แบบ constant-time และตอบรับ event ที่ถูกต้อง
- ตรวจ ownership/deduplication ก่อนตอบ, ค้น catalog ผ่าน Management API และเรียก OpenRouter
  ด้วย model ที่กำหนดใน environment
- ส่งต่อ human handoff ไปยัง Chatwoot team เมื่อถามเกินขอบเขตหรือพบ upstream failure

AI ไม่เข้าฐานข้อมูลโดยตรงและไม่รับ SQL จาก LLM; Management เป็นเจ้าของ catalog/knowledge
ส่วน Chatwoot เป็นเจ้าของ conversation และทีมรับช่วงต่อ

Run the API and worker together through the root Docker Compose stack. For local development,
set `REDIS_URL` (or `AI_QUEUE_REDIS_URL`) to a private Redis instance before starting the worker.
