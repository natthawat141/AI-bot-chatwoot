# คู่มือตั้งค่า LINE Official Account และให้ AI ช่วยจัดการ

เอกสารนี้ใช้กับระบบใน `D:\git\line` โดยมีเส้นทางหลักดังนี้

```text
ลูกค้าใน LINE
  -> LINE Platform
  -> HTTPS Webhook
  -> line-bot-service (FastAPI, port 8000)
  -> AI + ข้อมูลจาก line-bot-management
  -> ตอบกลับลูกค้าใน LINE
```

คู่มือนี้แบ่งเป็น 2 ส่วน:

1. ตั้งค่า LINE OA และ Messaging API ด้วยตนเอง
2. เชื่อม LINE Bot MCP เพื่อให้ AI/Codex ช่วยตรวจสอบ ส่งข้อความ และจัดการ Rich Menu อย่างปลอดภัย

> การสร้าง LINE OA ครั้งแรก การเลือก Provider การยอมรับเงื่อนไข และการออก Channel Access Token ควรให้เจ้าของบัญชีเป็นผู้ดำเนินการหรืออยู่ดูหน้าจอด้วยเสมอ

---

## 1. สิ่งที่ต้องมี

- บัญชี LINE หรือ Business ID
- สิทธิ์ Admin ของ LINE Official Account
- Node.js และ `npx` สำหรับ LINE Bot MCP
- Python virtual environment ของ `line-bot-service`
- ngrok หรือโดเมน HTTPS ที่ LINE Platform เข้าถึงได้
- OpenRouter API key สำหรับให้ AI ตอบข้อความ

เว็บที่ใช้:

- LINE Official Account Manager: <https://manager.line.biz/>
- LINE Developers Console: <https://developers.line.biz/console/>

---

## 2. ตั้งค่า LINE OA ด้วยตนเอง

### 2.1 สร้าง LINE Official Account

1. เข้า LINE Official Account Manager
2. ลงชื่อเข้าใช้ด้วย Business ID
3. สร้างบัญชีและกรอกชื่อธุรกิจ หมวดหมู่ และข้อมูลที่จำเป็น
4. ตรวจสอบว่าบัญชีปรากฏใน LINE Official Account Manager

ปัจจุบันต้องสร้าง LINE OA ก่อน แล้วจึงเปิดใช้ Messaging API จาก LINE Official Account Manager ไม่สามารถสร้าง Messaging API channel ใหม่โดยตรงจาก LINE Developers Console ได้แล้ว

### 2.2 เปิดใช้ Messaging API

1. เลือก LINE OA ที่ต้องการ
2. เข้าเมนู **Settings > Messaging API**
3. กดเปิดใช้งาน Messaging API
4. เลือก Provider ที่จะเป็นเจ้าของ Channel

> ระวัง: เมื่อผูก LINE OA กับ Provider แล้ว LINE ระบุว่าไม่สามารถเปลี่ยนหรือถอด Provider ภายหลังได้ จึงควรใช้ Provider ของบริษัทหรือลูกค้ารายนั้น ไม่ควรรวมกิจการที่ไม่เกี่ยวข้องไว้ใน Provider เดียวกัน

จากนั้นเข้า LINE Developers Console และตรวจสอบว่า Messaging API channel ถูกสร้างอยู่ภายใต้ Provider ที่เลือก

### 2.3 เตรียม Secret และ Token

ใน LINE Developers Console ให้เปิด Messaging API channel แล้วเก็บข้อมูลต่อไปนี้:

- `Channel secret` ใช้ตรวจสอบว่า Webhook มาจาก LINE จริง
- `Channel access token` ใช้เรียก Messaging API เช่น Reply, Push, Broadcast และ Rich Menu
- `Channel ID` ใช้ระบุ Channel ในบางงาน แต่โค้ด FastAPI ปัจจุบันยังไม่ต้องใช้ตอนรัน

สำหรับระบบปัจจุบัน สามารถเริ่มด้วย Long-lived channel access token จากแท็บ Messaging API ได้ง่ายที่สุด ส่วน Production ที่ต้องการควบคุมอายุ Token เข้มขึ้น LINE แนะนำ Channel access token v2.1

ห้ามทำสิ่งต่อไปนี้:

- ใส่ Secret หรือ Token ลง Git
- วาง Token ในเอกสาร คู่มือ รูปหน้าจอ หรือ Prompt ที่แชร์ให้บุคคลอื่น
- แสดง Token ใน log หรือคำตอบของ AI
- ใช้ Token เดียวกันกับทุกทีมโดยไม่จำเป็น

หากสงสัยว่า Token หลุด ให้ Revoke/ออก Token ใหม่ และเปลี่ยนค่าทั้งใน Bot และ MCP ทันที

---

## 3. ตั้งค่า `line-bot-service`

### 3.1 สร้างไฟล์ `.env`

```powershell
cd D:\git\line\line-bot-service
Copy-Item .env.example .env
```

กรอกอย่างน้อย:

```dotenv
LINE_CHANNEL_SECRET=<ใส่ Channel secret>
LINE_CHANNEL_ACCESS_TOKEN=<ใส่ Channel access token>
OPENROUTER_API_KEY=<ใส่ OpenRouter API key>

KNOWLEDGE_API_URL=http://127.0.0.1:8001
KNOWLEDGE_API_TOKEN=<โทเคนอ่านคลังความรู้>
ANALYTICS_API_URL=http://127.0.0.1:8001
ANALYTICS_API_TOKEN=<โทเคนบันทึกสถิติ>
```

ห้ามส่งไฟล์ `.env` ให้ลูกค้าหรือ Commit ขึ้น Git

### 3.2 เปิด Bot Server

```powershell
cd D:\git\line\line-bot-service
.\.venv\Scripts\python.exe -m uvicorn main:app --host 127.0.0.1 --port 8000
```

ตรวจสอบ Health:

```powershell
Invoke-RestMethod http://127.0.0.1:8000/health
```

Endpoint ของระบบ:

- `GET /health` สำหรับตรวจสอบสถานะ
- `POST /webhook` สำหรับรับ Event จาก LINE

หน้า `http://127.0.0.1:8000/` ตอบ `404 Not Found` ถือว่าปกติ เพราะระบบไม่ได้สร้างหน้าเว็บไว้ที่ `/`

### 3.3 เปิด HTTPS ด้วย ngrok

ครั้งแรกให้เพิ่ม authtoken โดยใช้ Token ของบัญชี ngrok ของตนเอง:

```powershell
ngrok config add-authtoken <NGROK_AUTHTOKEN>
```

เปิด Tunnel ไปยัง Bot ที่ port 8000:

```powershell
ngrok http 8000
```

ถ้าใช้ Reserved Domain ของโปรเจกต์:

```powershell
ngrok http 8000 --url=pod-skater-raging.ngrok-free.dev
```

Webhook URL ต้องเป็น URL สาธารณะและลงท้ายด้วย `/webhook` เช่น:

```text
https://pod-skater-raging.ngrok-free.dev/webhook
```

อย่าใช้ `localhost`, `127.0.0.1` หรือ port `8001` เป็น Webhook URL เพราะ LINE Platform เข้าถึงเครื่องเราโดยตรงไม่ได้ และ port `8001` เป็นเว็บจัดการข้อมูล ไม่ใช่ Bot webhook

---

## 4. ตั้งค่า Webhook ใน LINE Developers Console

1. เข้า Provider และเลือก Messaging API channel
2. เปิดแท็บ **Messaging API**
3. ที่ **Webhook URL** กด Edit
4. ใส่ URL สาธารณะ เช่น `https://<domain>/webhook`
5. กด **Verify**
6. ต้องได้ผลสำเร็จและ HTTP status `200`
7. เปิด **Use webhook**
8. แนะนำให้เปิด **Webhook redelivery** หลังระบบรองรับ Event ซ้ำแล้ว

ตอนกด Verify ทาง LINE จะส่ง `POST` ที่มี `events: []` มายัง Webhook ระบบจึงต้องตอบ `200` แม้ไม่มี Event ให้ประมวลผล

Bot ปัจจุบันตรวจสอบ `X-Line-Signature` ด้วย Channel secret ก่อนประมวลผล และมีการจำ `webhookEventId` เพื่อช่วยป้องกันการทำงานซ้ำ

### ตั้งค่า Response Settings เพื่อไม่ให้ตอบซ้ำ

ใน LINE Official Account Manager ให้ตรวจสอบ **Settings > Response settings**:

- เปิด Webhook/โหมด Bot ตามหน้าจอที่ LINE แสดง
- ปิด Auto-reply messages หากต้องการให้ AI Bot เป็นผู้ตอบหลัก
- Greeting message จะเปิดไว้ก็ได้ถ้าตั้งใจใช้ แต่ระหว่างทดสอบควรปิดเพื่อลดความสับสน

ถ้า Auto-reply ของ OA และ AI Bot เปิดตอบข้อความเดียวกันพร้อมกัน ลูกค้าจะได้รับคำตอบซ้ำสองชุด

---

## 5. ทดสอบการทำงานแบบครบเส้นทาง

ตรวจตามลำดับนี้:

1. `line-bot-management` เปิดที่ port 8001
2. `line-bot-service` เปิดที่ port 8000
3. ngrok ชี้เข้า `http://127.0.0.1:8000`
4. `GET /health` ตอบปกติ
5. LINE Developers กด Verify ผ่าน
6. **Use webhook** เปิดอยู่
7. เพิ่ม LINE OA เป็นเพื่อน
8. ส่ง `hi` หรือถามข้อมูลแพ็กเกจ

ผลที่คาดหวัง:

- ข้อความทั่วไป: AI ตอบเป็น Text Message
- ดูบริการและแพ็กเกจ: Bot ตอบ Flex Message Carousel
- ดูโปรโมชัน: Bot ตอบ Flex Message Carousel เฉพาะรายการที่มีราคาโปร
- ขอข้อมูลติดต่อและแผนที่: Bot ส่ง Native Location Message

---

## 6. Rich Menu: ทำเองหรือให้ AI ทำ

LINE มีเครื่องมือสร้าง Rich Menu สองทาง:

| วิธี | เหมาะกับ | ข้อจำกัด |
|---|---|---|
| LINE Official Account Manager | ตั้งค่าง่าย มี GUI และดูสถิติใน Manager | AI/Messaging API อ่านหรือแก้ Rich Menu ชุดเดียวกันไม่ได้ |
| Messaging API / LINE Bot MCP | ให้ระบบหรือ AI สร้าง สลับ และจัดการได้ | ต้องควบคุมสิทธิ์และต้องดูสถิติผ่าน API |

Rich Menu หนึ่งชุดต้องเลือกเครื่องมือเดียว ชุดที่สร้างใน OA Manager แก้ผ่าน API/MCP ไม่ได้ และชุดที่สร้างผ่าน API ก็แก้ใน OA Manager ไม่ได้

Rich Menu ปัจจุบันของ W+ Medic Clinic ใช้พื้นที่ 1200 x 810:

| พื้นที่ | พิกัด | Action แนะนำ |
|---|---|---|
| A ด้านบน | `x=0, y=0, w=1200, h=405` | Message: `ดูบริการและแพ็กเกจ` |
| B ล่างซ้าย | `x=0, y=405, w=400, h=405` | Message: `ดูโปรโมชัน` |
| C ล่างกลาง | `x=400, y=405, w=400, h=405` | Message: `ช่วยแนะนำบริการให้หน่อย` |
| D ล่างขวา | `x=800, y=405, w=400, h=405` | Message: `ขอข้อมูลติดต่อและแผนที่` |

เมื่อ Action เป็นชนิด Message ข้อความจะถูกส่งเข้าห้องแชตเหมือนผู้ใช้พิมพ์เอง จากนั้น Webhook และโค้ด Bot จะเป็นผู้ตัดสินใจว่าจะตอบ Text, Flex Carousel หรือ Location

ดูรายละเอียด Flex Message เพิ่มเติมได้ที่ [คู่มือ LINE Flex Message Carousel](FLEX_CAROUSEL_GUIDE_TH.md)

---

## 7. เชื่อม LINE Bot MCP ให้ AI/Codex

LINE Bot MCP Server เป็นโครงการทางการของ LINE แต่ยังระบุว่าเป็น Preview และอาจยังรองรับ Messaging API ไม่ครบทุกฟังก์ชัน

ความสามารถหลักที่มีในรุ่นปัจจุบัน ได้แก่:

- ตรวจโควตาข้อความด้วย `get_message_quota`
- ส่ง Text/Flex แบบ Push และ Broadcast
- ดูรายชื่อ Rich Menu
- สร้าง ตั้งเป็น Default ยกเลิก Default และลบ Rich Menu ที่สร้างผ่าน API
- ดู Follower IDs

MCP นี้ไม่ได้แทนทุกหน้าของ LINE OA Manager เช่น โปรไฟล์ธุรกิจ Response Settings และการตั้งค่าเริ่มต้นบางส่วนยังต้องทำในหน้าเว็บ

### 7.1 เพิ่ม MCP ใน Codex โดยไม่พิมพ์ Token ออกหน้าจอ

ตรวจสอบก่อนว่ามี config เดิมหรือยัง:

```powershell
Select-String -Path "$HOME\.codex\config.toml" -Pattern '^\[mcp_servers\.line-bot\]$'
```

ถ้ายังไม่มี ให้ใช้คำสั่งนี้ โดยอ่าน Token จาก `.env` และไม่แสดงค่า Token:

```powershell
$envFile = "D:\git\line\line-bot-service\.env"
$tokenLine = Get-Content -LiteralPath $envFile |
    Where-Object { $_ -match '^LINE_CHANNEL_ACCESS_TOKEN=' } |
    Select-Object -First 1

if (-not $tokenLine) {
    throw "ไม่พบ LINE_CHANNEL_ACCESS_TOKEN ใน $envFile"
}

$token = ($tokenLine -split '=', 2)[1].Trim()
if ([string]::IsNullOrWhiteSpace($token)) {
    throw "LINE_CHANNEL_ACCESS_TOKEN ยังว่าง"
}

$configDir = "$HOME\.codex"
$configFile = "$configDir\config.toml"
New-Item -ItemType Directory -Force $configDir | Out-Null

if (Test-Path $configFile) {
    $exists = Select-String -Path $configFile -Pattern '^\[mcp_servers\.line-bot\]$' -Quiet
    if ($exists) {
        throw "มี mcp_servers.line-bot อยู่แล้ว กรุณาแก้ block เดิม ห้าม append ซ้ำ"
    }
}

$block = @"

[mcp_servers.line-bot]
command = "npx"
args = ["@line/line-bot-mcp-server"]
env = { NPM_CONFIG_IGNORE_SCRIPTS = "true", CHANNEL_ACCESS_TOKEN = "$token", DESTINATION_USER_ID = "" }
"@

[System.IO.File]::AppendAllText(
    $configFile,
    $block,
    [System.Text.UTF8Encoding]::new($false)
)

Write-Host "เพิ่ม LINE Bot MCP แล้วโดยไม่แสดง Token"
```

ไฟล์ `C:\Users\<user>\.codex\config.toml` จะมี Token อยู่จริง จึงห้าม Commit แชร์ หรือถ่ายภาพส่วนนี้

### 7.2 รีสตาร์ทและทดสอบแบบ Read-only

1. ปิดแล้วเปิด Codex ใหม่
2. เปิด `/mcp` และตรวจว่ามี Server ชื่อ `line-bot`
3. สั่ง AI ว่า:

```text
ใช้ LINE Bot MCP รัน get_message_quota เท่านั้น
ห้ามส่งข้อความ ห้ามแก้ Rich Menu และห้ามเปลี่ยนข้อมูลใด ๆ
```

ถ้าอ่านโควตาได้ แปลว่า MCP เชื่อมต่อกับ LINE OA สำเร็จ

---

## 8. กติกาความปลอดภัยสำหรับ AI

การมี MCP หมายถึง AI ถือสิทธิ์เดียวกับ Channel Access Token จึงต้องกำหนดกติกาไว้ใน Prompt ทุกครั้ง

### ทำได้ทันทีโดยไม่ต้องยืนยัน

- `get_message_quota`
- `get_rich_menu_list`
- อ่านและสรุปโครงสร้าง Rich Menu ที่สร้างผ่าน API
- สร้าง Draft ข้อความหรือ Draft Flex JSON โดยยังไม่ส่ง
- ตรวจภาพและคำนวณพิกัด Action โดยยังไม่เปิดใช้งาน

### ต้องขอคำยืนยันก่อนทุกครั้ง

- `broadcast_text_message` และ `broadcast_flex_message`
- `push_text_message` และ `push_flex_message` ไปยังผู้ใช้จริง
- `create_rich_menu` หากคำสั่งจะสร้างและตั้งเป็น Default ทันที
- `set_rich_menu_default` และ `cancel_rich_menu_default`
- `delete_rich_menu`
- เปลี่ยน Rich Menu ที่ลูกค้ากำลังใช้งาน
- ดึงหรือแสดง Follower IDs โดยไม่มีเหตุผลทางงานชัดเจน
- Revoke หรือเปลี่ยน Channel Access Token

ก่อนยืนยัน AI ต้องแสดงอย่างน้อย:

- จะทำอะไร
- ส่งถึงใครหรือมีผลกับผู้ติดตามทั้งหมดหรือไม่
- ข้อความ/ภาพ/Action ที่จะใช้
- Rich Menu ID ที่จะเปลี่ยนหรือลบ
- ผลกระทบและวิธีย้อนกลับ

คำว่า `ยืนยัน` ควรใช้กับรายการที่ AI สรุปให้เห็นในข้อความก่อนหน้าเท่านั้น ไม่ควรถือเป็นสิทธิ์ถาวรสำหรับงานครั้งถัดไป

### Prompt มาตรฐานสำหรับใช้กับ AI

```text
คุณมี MCP server ชื่อ line-bot เชื่อมกับ LINE OA ของ W+ Medic Clinic

กติกา:
1. งานอ่านข้อมูล เช่น ตรวจโควตาและดูรายชื่อ Rich Menu ทำได้ทันที
2. ก่อน Broadcast, Push ไปยังผู้ใช้จริง, สร้าง/ตั้ง Default/ยกเลิก Default/ลบ Rich Menu
   ให้หยุดและขอคำยืนยันจากฉันก่อนทุกครั้ง
3. ก่อนขอคำยืนยัน ให้แสดงผู้รับ เนื้อหา Action หรือ Rich Menu ID และผลกระทบทั้งหมด
4. ห้ามแสดง Channel Access Token, User ID หรือข้อมูลส่วนบุคคลเกินความจำเป็น
5. ห้ามถือคำยืนยันครั้งก่อนเป็นสิทธิ์สำหรับคำสั่งครั้งใหม่

เริ่มต้นด้วย get_message_quota เพื่อยืนยันการเชื่อมต่อเท่านั้น
```

---

## 9. ให้ AI ช่วย Setup ได้ถึงระดับไหน

| งาน | AI ช่วยได้หรือไม่ | เงื่อนไข |
|---|---|---|
| แนะนำขั้นตอนสร้าง LINE OA | ได้ | เจ้าของบัญชีเป็นผู้กรอกและยืนยัน |
| เลือก Provider | ช่วยวิเคราะห์ได้ | เจ้าของต้องตัดสินใจ เพราะเปลี่ยนภายหลังไม่ได้ |
| เปิด Messaging API ผ่านหน้าเว็บ | ช่วยนำทางหน้าจอได้ | เจ้าของต้องอนุมัติและดูการทำงาน |
| สร้าง/อ่าน Channel Access Token | ไม่ควรให้ AI แสดง Token | เจ้าของนำไปเก็บใน Secret storage เอง |
| ตั้ง Webhook URL | ช่วยตรวจ URL และทดสอบได้ | การแก้หน้า Console ต้องได้รับอนุญาต |
| ตรวจโควตา | ได้ทันที | Read-only |
| ร่าง Broadcast หรือ Flex | ได้ทันที | ยังห้ามส่ง |
| ส่ง Broadcast/Push | ได้ | ต้องยืนยันก่อนส่งทุกครั้ง |
| สร้างหรือเปลี่ยน Rich Menu ผ่าน MCP | ได้ | ต้องยืนยันก่อนมีผลกับลูกค้า |
| แก้ Rich Menu ที่สร้างใน OA Manager ผ่าน MCP | ไม่ได้ | ต้องแก้ใน OA Manager หรือสร้างชุด API ใหม่ |

ถ้าต้องการให้ AI จัดการ Rich Menu แบบอัตโนมัติ ให้สร้าง Rich Menu ชุดใหม่ผ่าน Messaging API/MCP แล้วตั้งเป็น Default หลังผู้ใช้ยืนยัน การตั้ง Default ผ่าน API มีลำดับความสำคัญสูงกว่า Default ที่สร้างใน OA Manager

สำหรับ Rich Menu ที่ต้องใช้ภาพแบรนด์สวยและควบคุมพิกัดละเอียด แนะนำให้เตรียมภาพและ JSON ในโค้ด ตรวจ Preview ก่อน แล้วค่อยเรียก Messaging API โดยตรง ส่วน `create_rich_menu` ของ MCP เหมาะกับการสร้างชุดทดลองอย่างรวดเร็ว

---

## 10. Troubleshooting

| อาการ | จุดที่ต้องตรวจ |
|---|---|
| Verify ได้ `404 Not Found` | URL ต้องลงท้าย `/webhook` และ ngrok ต้องชี้ port 8000 |
| Verify ไม่ได้/ไม่ใช่ `200` | Bot server เปิดหรือไม่, ngrok ยังทำงานหรือไม่, URL ถูกหรือไม่ |
| ส่ง LINE แล้ว Bot ไม่ตอบ | เปิด **Use webhook**, ปิด Auto-reply ที่ชนกัน, ตรวจ `.env` และ log ของ Uvicorn |
| เปิด URL แล้วเห็น 404 ที่ `/` | ปกติ ให้ใช้ `/health` หรือ `/webhook` ตามหน้าที่ |
| Bot ตอบซ้ำ | ปิด Auto-reply message ของ LINE OA หรือจัด Greeting ให้ไม่ชนกับ Bot |
| Bot ตอบ Text แทน Flex | ตรวจข้อความ Action ใน Rich Menu ให้ตรงกับ Keyword ที่โค้ดรองรับ |
| Rich Menu ใหม่ยังไม่ขึ้น | ปิดแล้วเปิดห้องแชตใหม่ และรอประมาณหนึ่งนาที |
| AI ไม่เห็น `line-bot` MCP | ตรวจ `config.toml`, ปิด/เปิด Codex ใหม่ และเช็ก `/mcp` |
| MCP ส่งไม่ได้ | ตรวจ Token หมดอายุ/ถูก Revoke, สิทธิ์ Channel และโควตาข้อความ |
| MCP แก้ Rich Menu ใน Manager ไม่ได้ | เป็นข้อจำกัดตามการแบ่งเครื่องมือของ LINE ต้องแก้ในเครื่องมือที่สร้างเมนูนั้น |

---

## 11. Checklist ก่อนส่งมอบลูกค้า

- [ ] LINE OA เป็นของบัญชีลูกค้าหรือบริษัท ไม่ผูกกับบัญชีส่วนตัวของ Developer เพียงคนเดียว
- [ ] Provider ถูกต้องและมี Admin สำรอง
- [ ] Secret และ Token ไม่อยู่ใน Git
- [ ] Webhook ใช้ HTTPS และ Verify ผ่าน
- [ ] เปิด Use webhook
- [ ] ปิด Auto-reply ที่ทำให้ตอบซ้ำ
- [ ] Bot ตรวจ `X-Line-Signature`
- [ ] ทดสอบ Text, Flex Carousel, Promotion และ Location
- [ ] ทดสอบกรณี Knowledge API ใช้งานไม่ได้
- [ ] กำหนดผู้มีสิทธิ์ Broadcast และเปลี่ยน Rich Menu
- [ ] AI ต้องขออนุมัติก่อน Action ที่มีผลต่อลูกค้าจริงทุกครั้ง
- [ ] มีวิธี Revoke/Rotate Token เมื่อพนักงานออกหรือ Token รั่ว

---

## เอกสารอ้างอิงทางการ

- [Get started with the Messaging API](https://developers.line.biz/en/docs/messaging-api/getting-started/)
- [Build a bot](https://developers.line.biz/en/docs/messaging-api/building-bot/)
- [Receive messages (webhook)](https://developers.line.biz/en/docs/messaging-api/receiving-messages/)
- [Verify webhook URL](https://developers.line.biz/en/docs/messaging-api/verify-webhook-url/)
- [Verify webhook signature](https://developers.line.biz/en/docs/messaging-api/verify-webhook-signature/)
- [Channel access token](https://developers.line.biz/en/docs/basics/channel-access-token/)
- [Rich menus overview](https://developers.line.biz/en/docs/messaging-api/rich-menus-overview/)
- [LINE Bot MCP Server](https://github.com/line/line-bot-mcp-server)

