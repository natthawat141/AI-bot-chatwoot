# Chatwoot Container

โครงนี้อิงบริการที่ Chatwoot ระบุสำหรับ production: Rails web, Sidekiq worker, PostgreSQL และ Redis โดยไม่คัดลอก source code ของ Chatwoot เข้ามาใน repo

## การใช้งาน

บริการ Chatwoot ถูกรวมอยู่ใน root `compose.yml` ร่วมกับ Caddy, Laravel Management และ AI
จึงควรสั่งงานจาก root ของ repository ด้วย `docker compose up -d`

bootstrap จะสร้าง account, ทีมสำหรับ human handoff, Agent Bot และ token สำหรับ AI service
โดยไม่พิมพ์ค่า secret ออกทาง log

```bash
docker compose ps
docker compose logs --tail=50 chatwoot-bootstrap
```

สำหรับ production ให้ใช้ reverse proxy พร้อม HTTPS, SMTP, object storage และระบบ backup ก่อนเปิดรับ traffic จริง ห้าม commit `.env`
