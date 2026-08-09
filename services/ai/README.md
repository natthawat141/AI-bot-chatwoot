# Python AI Service

โครง FastAPI สำหรับรับ event จาก Chatwoot และประสานงานกับ Management API

ความสามารถใน Version 1:

- `GET /health` ใช้ตรวจ process health
- `POST /webhooks/chatwoot?token=...` ตรวจ token แบบ constant-time และตอบรับ event ที่ถูกต้อง
- ตรวจ ownership/deduplication ก่อนตอบ, ค้น catalog ผ่าน Management API และเรียก OpenRouter
  ด้วย model ที่กำหนดใน environment
- ส่งต่อ human handoff ไปยัง Chatwoot team เมื่อถามเกินขอบเขตหรือพบ upstream failure

AI ไม่เข้าฐานข้อมูลโดยตรงและไม่รับ SQL จาก LLM; Management เป็นเจ้าของ catalog/knowledge
ส่วน Chatwoot เป็นเจ้าของ conversation และทีมรับช่วงต่อ
