# คู่มือทำ LINE Flex Message Carousel สำหรับโปรเจกต์ W+ Medic

คู่มือนี้อธิบายระบบที่ใช้งานจริงใน `line-bot-service` เพื่อให้สามารถอ่าน แก้ไข และสร้าง Flex Carousel เพิ่มเองได้

เอกสารทางการที่ควรเปิดประกอบ:

- [Send Flex Messages](https://developers.line.biz/en/docs/messaging-api/using-flex-messages/)
- [Flex Message layout](https://developers.line.biz/en/docs/messaging-api/flex-message-layout/)
- [Actions](https://developers.line.biz/en/docs/messaging-api/actions/)
- [Rich menus overview](https://developers.line.biz/en/docs/messaging-api/rich-menus-overview/)
- [Flex Message Simulator](https://developers.line.biz/flex-simulator/)

## 1. Rich Menu กับ Flex Carousel เป็นคนละส่วน

Rich Menu คือภาพเมนูด้านล่างของห้องแชต ส่วน Flex Carousel คือข้อความรูปแบบการ์ดที่บอตส่งกลับมา

```text
ผู้ใช้กด Rich Menu
        │
        ▼
LINE ส่งข้อความ เช่น "ดูบริการและแพ็กเกจ"
        │
        ▼
POST https://pod-skater-raging.ngrok-free.dev/webhook
        │
        ▼
ngrok ส่งต่อไป http://localhost:8000/webhook
        │
        ▼
webhook.py ตรวจว่าข้อความต้องเปิด Carousel หรือไม่
        │
        ▼
ดึงแพ็กเกจจาก AI Knowledge API
        │
        ▼
package_carousel.py สร้าง FlexMessage
        │
        ▼
line_client.py ส่ง Reply Message กลับไปที่ LINE
```

ถ้าตั้ง Rich Menu เป็น `Text action` ข้อความจากปุ่มจะปรากฏเหมือนผู้ใช้พิมพ์เอง และ LINE จะส่ง `message event` เข้า webhook ตามปกติ

## 2. Action ที่ระบบใช้ตอนนี้

| พื้นที่ | ข้อความที่ Rich Menu ส่ง | ผลลัพธ์ |
|---|---|---|
| A | `ดูบริการและแพ็กเกจ` | Flex Carousel แพ็กเกจที่เปิดใช้งาน |
| B | `ดูโปรโมชัน` | Flex Carousel เฉพาะรายการที่มี `sale_price` |
| C | `ช่วยแนะนำบริการให้หน่อย` | AI ตอบเป็นข้อความปกติ |
| D | `ขอข้อมูลติดต่อและแผนที่` | Location Message |

ข้อความของ A และ B ต้องตรงกับค่าที่ประกาศใน `PACKAGE_MENU_QUERIES` และ `PROMOTION_MENU_QUERIES`

## 3. ไฟล์สำคัญ

```text
app/
├── routers/
│   └── webhook.py              รับ event และเลือกว่าจะตอบแบบใด
└── services/
    ├── knowledge_api.py        ดึงแพ็กเกจจากเว็บ AI Knowledge
    ├── package_carousel.py     สร้าง Flex Carousel และการ์ดแต่ละใบ
    └── line_client.py          ส่งข้อความกลับ LINE Messaging API
```

Tests ที่เกี่ยวข้อง:

```text
tests/test_package_carousel.py
tests/test_webhook.py
```

## 4. โครงสร้างของ Flex Message

Flex Message หนึ่งข้อความมีโครงสร้างหลักดังนี้:

```text
FlexMessage
└── FlexCarousel
    ├── FlexBubble: แพ็กเกจที่ 1
    ├── FlexBubble: แพ็กเกจที่ 2
    └── FlexBubble: แพ็กเกจที่ 3 ...
```

Bubble แต่ละใบแบ่งได้เป็น:

- `header` ชื่อแบรนด์หรือหมวดหมู่
- `hero` รูปภาพด้านบน โดยไม่จำเป็นต้องมี
- `body` ชื่อ รายละเอียด และราคา
- `footer` ปุ่มให้ผู้ใช้กดทำรายการต่อ

ในโปรเจกต์นี้จำกัดไว้ไม่เกิน 10 การ์ดด้วย `MAX_CAROUSEL_ITEMS` เพื่อไม่ให้ข้อความยาวและหนักเกินไป

## 5. จุดที่ตรวจข้อความจาก Rich Menu

ฟังก์ชันนี้อยู่ใน `app/services/package_carousel.py`:

```python
PACKAGE_MENU_QUERIES = {
    "ดูบริการและแพ็กเกจ",
    "ดูบริการและแพคเกจ",
    "ดูแพ็กเกจ",
    "ดูแพคเกจ",
}

PROMOTION_MENU_QUERIES = {"ดูโปรโมชัน", "ดูโปรโมชั่น"}


def carousel_kind(text: str) -> str | None:
    normalized = " ".join((text or "").casefold().split())
    if normalized in PACKAGE_MENU_QUERIES:
        return "packages"
    if normalized in PROMOTION_MENU_QUERIES:
        return "promotions"
    return None
```

ถ้าจะเพิ่มคำสั่งใหม่ เช่น `ดูรายการทั้งหมด` ให้เพิ่มเข้า set ที่เหมาะสม:

```python
PACKAGE_MENU_QUERIES = {
    "ดูบริการและแพ็กเกจ",
    "ดูรายการทั้งหมด",
}
```

อย่าใช้การตรวจแบบกว้างเกินไป เช่น `if "โปร" in text` เพราะข้อความธรรมดาของลูกค้าอาจเปิด Carousel โดยไม่ตั้งใจ

## 6. การเลือกข้อมูลที่จะสร้างการ์ด

```python
def select_packages(packages: tuple[dict, ...], kind: str) -> list[dict]:
    rows = list(packages)
    if kind == "promotions":
        rows = [row for row in rows if row.get("sale_price") is not None]
    return rows[:MAX_CAROUSEL_ITEMS]
```

- `packages` แสดงรายการที่ API ส่งมา
- `promotions` กรองเฉพาะรายการที่มี `sale_price`
- ตัดรายการเหลือจำนวนที่ระบบกำหนด

ข้อมูลที่การ์ดรองรับ:

| Field | ใช้ทำอะไร | จำเป็นหรือไม่ |
|---|---|---|
| `code` | รหัสที่ส่งกลับเมื่อกดสอบถาม | ควรมี |
| `category` | หมวดที่แสดงเหนือชื่อ | ไม่จำเป็น |
| `name_th` | ชื่อแพ็กเกจ | จำเป็น |
| `description_th` | รายละเอียดสั้น | ไม่จำเป็น |
| `price` | ราคาปกติ | ไม่จำเป็น |
| `sale_price` | ราคาโปรโมชัน | ไม่จำเป็น |
| `image_url` | รูป Hero แบบ HTTPS | ไม่จำเป็นและยังไม่มีใน CRUD ปัจจุบัน |

## 7. การสร้าง Carousel

```python
def build_package_carousel(packages: list[dict], kind: str) -> FlexMessage:
    title = (
        "โปรโมชัน W+ Medic"
        if kind == "promotions"
        else "บริการและแพ็กเกจ W+ Medic"
    )

    return FlexMessage(
        altText=title,
        contents=FlexCarousel(
            contents=[_bubble(package) for package in packages]
        ),
    )
```

`altText` สำคัญ เพราะใช้เป็นข้อความทดแทนใน notification หรือสภาพแวดล้อมที่แสดง Flex ไม่สมบูรณ์

## 8. ตัวอย่าง Bubble แบบย่อ

```python
def make_simple_bubble(package: dict) -> FlexBubble:
    return FlexBubble(
        body=FlexBox(
            layout="vertical",
            contents=[
                FlexText(
                    text=package["name_th"],
                    weight="bold",
                    size="lg",
                    wrap=True,
                ),
                FlexText(
                    text=package.get("description_th") or "สอบถามรายละเอียด",
                    size="sm",
                    color="#6B7280",
                    wrap=True,
                    margin="md",
                ),
            ],
        ),
        footer=FlexBox(
            layout="vertical",
            contents=[
                FlexButton(
                    style="primary",
                    action=MessageAction(
                        label="สอบถาม",
                        text=f"สนใจแพ็กเกจ {package['code']}",
                    ),
                )
            ],
        ),
    )
```

เมื่อกดปุ่ม `MessageAction` LINE จะส่งข้อความ เช่น `สนใจแพ็กเกจ PKG-001` กลับเข้า webhook จากนั้น AI จะค้นหาแพ็กเกจด้วยรหัสและตอบรายละเอียดต่อได้

## 9. รูปภาพบนการ์ด

โค้ดรองรับ `image_url` อยู่แล้ว:

```python
hero = None
image_url = package.get("image_url")

if _is_https_url(image_url):
    hero = FlexImage(
        url=image_url,
        size="full",
        aspectRatio="20:13",
        aspectMode="cover",
    )
```

ข้อควรจำ:

- URL ต้องเป็น `https://`
- LINE ต้องดาวน์โหลดรูปจากอินเทอร์เน็ตได้ จึงใช้ `localhost` หรือ path ในเครื่องไม่ได้
- ควรใช้ภาพขนาดและสัดส่วนใกล้กันทุกแพ็กเกจ
- ควรบีบอัดรูปก่อนใช้งานเพื่อให้โหลดเร็ว
- ถ้าไม่มีรูป โค้ดจะไม่ใส่ `hero` และการ์ดยังใช้งานได้

ถ้าจะเพิ่มรูปใน CRUD ภายหลัง ต้องเพิ่ม `image_url` แบบ optional ใน database, model, validation, API resource และฟอร์มแพ็กเกจ

## 10. จุดที่ webhook เลือกส่ง Carousel

แนวคิดใน `app/routers/webhook.py` คือ:

```python
if kind := carousel_kind(event.message.text):
    snapshot = knowledge_client.fetch_snapshot()
    packages = select_packages(snapshot.packages, kind) if snapshot else []

    if packages:
        message = build_package_carousel(packages, kind)
        line_client.reply_message(event.reply_token, message)
    else:
        line_client.reply_text(
            event.reply_token,
            "ขณะนี้ยังไม่มีแพ็กเกจที่เปิดใช้งาน",
        )
    return
```

คำสั่ง `return` สำคัญ เพราะป้องกันไม่ให้ event เดียวกันไหลต่อไปหา AI และตอบซ้ำ

## 11. การส่ง Flex Message เข้า LINE

ใน `app/services/line_client.py` มี method กลาง:

```python
def reply_message(self, reply_token: str, message: Message) -> bool:
    return self._reply(reply_token, message)
```

และ `_reply()` ส่งข้อความผ่าน `MessagingApi.reply_message()` โดยใช้ reply token จาก webhook event

Reply token มีอายุสั้น จึงควรสร้างและส่งข้อความภายในงาน webhook ทันที ไม่ควรเก็บไว้ใช้ภายหลัง

## 12. วิธีทดสอบ

### รัน automated tests

```powershell
cd D:\git\line\line-bot-service
.\.venv\Scripts\python.exe -m pytest -q
```

### ตรวจเซิร์ฟเวอร์ในเครื่อง

```powershell
Invoke-RestMethod http://127.0.0.1:8000/health
```

ผลที่ถูกต้อง:

```json
{"status":"ok"}
```

### ตรวจ ngrok ว่าชี้ถูกพอร์ต

```powershell
$tunnels = Invoke-RestMethod http://127.0.0.1:4040/api/tunnels
$tunnels.tunnels | Select-Object public_url,@{n='forward_to';e={$_.config.addr}}
```

ค่าที่ต้องการ:

```text
public_url: https://pod-skater-raging.ngrok-free.dev
forward_to: http://localhost:8000
```

Webhook URL ที่ใส่ใน LINE Developers Console คือ:

```text
https://pod-skater-raging.ngrok-free.dev/webhook
```

ห้ามใส่ `http://localhost:8000` ใน LINE เพราะ LINE Platform เข้าถึง localhost ของเครื่องเราไม่ได้

### ทดสอบบนโทรศัพท์

1. ปิดแล้วเปิดห้องแชต W+ Medic ใหม่
2. กด `ดูบริการและแพ็กเกจ`
3. ต้องเห็นการ์ดเลื่อนซ้าย–ขวา
4. กด `สอบถามแพ็กเกจนี้`
5. ต้องเห็นข้อความรหัสแพ็กเกจและคำตอบจาก AI

## 13. วิธีตรวจเมื่อระบบไม่ส่งการ์ด

### กดแล้ว AI ตอบเป็นข้อความปกติ

ตรวจตามลำดับ:

1. ข้อความจาก Rich Menu ตรงกับ `PACKAGE_MENU_QUERIES` หรือไม่
2. ngrok ชี้ `localhost:8000` หรือยังชี้พอร์ตบอตเก่า
3. รีสตาร์ต Uvicorn หลังแก้โค้ดหรือยัง
4. ดู log ว่า request เข้า service นี้หรือไม่

```powershell
Get-Content D:\git\line\line-bot-service\uvicorn.stdout.log -Tail 50
Get-Content D:\git\line\line-bot-service\uvicorn.stderr.log -Tail 50
```

### ตอบว่าไม่มีแพ็กเกจ

ตรวจว่า:

- `KNOWLEDGE_API_URL` และ `KNOWLEDGE_API_TOKEN` ถูกต้อง
- เว็บจัดการพอร์ต 8001 ทำงาน
- แพ็กเกจเป็น active, published และอยู่ในช่วงวันที่ใช้งาน

### ไม่มีรูปบนการ์ด

เป็นพฤติกรรมปกติของระบบปัจจุบัน เพราะ API ยังไม่มี `image_url` การ์ดจะแสดงข้อความ ราคา และปุ่มโดยไม่มี Hero image

### LINE ไม่ตอบเลย

ตรวจว่า:

- Uvicorn และ ngrok ยังเปิดอยู่
- LINE webhook URL ลงท้ายด้วย `/webhook`
- Use webhook เปิดใช้งานใน LINE Developers Console
- Channel secret และ channel access token เป็นของ channel เดียวกัน

## 14. แนวทางฝึกทำเอง

1. เปลี่ยนสีใน `_bubble()` แล้วรันทดสอบ
2. เพิ่มข้อความหมวดหมู่หรือระยะเวลาบริการ
3. สร้างแพ็กเกจทดสอบหนึ่งรายการใน AI Knowledge
4. เปิด [Flex Message Simulator](https://developers.line.biz/flex-simulator/) แล้วลองสร้าง Bubble แบบ JSON
5. เมื่อหน้าตาถูกใจจึงย้ายโครงสร้างกลับมาเป็น Python SDK objects

แก้ทีละส่วนและรันทดสอบทุกครั้ง จะหาจุดผิดได้ง่ายกว่าการเปลี่ยนทั้ง Carousel พร้อมกัน
