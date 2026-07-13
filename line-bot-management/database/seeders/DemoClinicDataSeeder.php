<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\KnowledgeEntry;
use App\Models\PackageCategory;
use App\Models\ServicePackage;
use Illuminate\Database\Seeder;

/**
 * SIMULATED demo catalog for local/dev testing of the LINE bot pipeline.
 *
 * Prices, promotions, and terms below are INVENTED for demonstrations and
 * must never be seeded into a production database. Categories reuse the ones
 * created by WMedicClinicSeeder. Idempotent via updateOrCreate on `code`.
 */
class DemoClinicDataSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = PackageCategory::query()->pluck('id', 'slug');

        $today = now()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $packages = [
            [
                'code' => 'LSR-001',
                'category' => 'laser-skin',
                'name_th' => 'เลเซอร์หน้าใส Q-Switch',
                'name_en' => 'Q-Switch Brightening Laser',
                'description_th' => 'เลเซอร์ลดจุดด่างดำ รอยสิว กระตุ้นผิวกระจ่างใส ใช้เวลาประมาณ 30 นาที',
                'price' => 1500,
                'sale_price' => 990,
                'duration_minutes' => 30,
                'keywords' => 'เลเซอร์, หน้าใส, จุดด่างดำ, รอยสิว, q-switch, laser',
                'terms' => 'โปรโมชั่นถึงสิ้นเดือนนี้ จำกัด 1 สิทธิ์ต่อท่าน',
                'effective_from' => $today,
                'effective_until' => $endOfMonth,
            ],
            [
                'code' => 'LSR-002',
                'category' => 'laser-skin',
                'name_th' => 'เลเซอร์กำจัดขนรักแร้ (คอร์ส 5 ครั้ง)',
                'name_en' => 'Underarm Hair Removal (5 sessions)',
                'description_th' => 'เลเซอร์กำจัดขนรักแร้ด้วยเครื่อง Diode Laser คอร์ส 5 ครั้ง',
                'price' => 3990,
                'sale_price' => 2990,
                'duration_minutes' => 20,
                'keywords' => 'กำจัดขน, รักแร้, ขนรักแร้, hair removal, diode',
                'terms' => 'โปรโมชั่นถึงสิ้นเดือนนี้ ระยะห่างแต่ละครั้ง 4-6 สัปดาห์',
                'effective_from' => $today,
                'effective_until' => $endOfMonth,
            ],
            [
                'code' => 'LSR-003',
                'category' => 'laser-skin',
                'name_th' => 'โปรแกรมรักษาคีลอยด์ (ฉีดยา)',
                'name_en' => 'Keloid Injection Treatment',
                'description_th' => 'ฉีดยารักษาแผลเป็นนูนคีลอยด์ ราคาต่อจุด แพทย์ประเมินก่อนรักษาทุกครั้ง',
                'price' => 800,
                'sale_price' => null,
                'duration_minutes' => 15,
                'keywords' => 'คีลอยด์, คีรอย, แผลเป็นนูน, แผลนูน, keloid',
                'terms' => 'ราคาต่อจุด จำนวนครั้งขึ้นอยู่กับการประเมินของแพทย์',
                'effective_from' => $today,
                'effective_until' => null,
            ],
            [
                'code' => 'LSR-004',
                'category' => 'laser-skin',
                'name_th' => 'โปรแกรมดูแลสิวครบวงจร',
                'name_en' => 'Complete Acne Care Program',
                'description_th' => 'ทำความสะอาดผิว กดสิว ฉายแสงลดการอักเสบ พร้อมยาทากลับบ้าน',
                'price' => 1290,
                'sale_price' => null,
                'duration_minutes' => 45,
                'keywords' => 'สิว, กดสิว, สิวอักเสบ, ฉายแสง, acne',
                'terms' => null,
                'effective_from' => $today,
                'effective_until' => null,
            ],
            [
                'code' => 'LDY-001',
                'category' => 'for-lady',
                'name_th' => 'ตรวจสุขภาพภายในสำหรับผู้หญิง',
                'name_en' => 'Lady Wellness Check',
                'description_th' => 'ตรวจภายในและให้คำปรึกษาโดยแพทย์หญิง เป็นส่วนตัว',
                'price' => 1200,
                'sale_price' => null,
                'duration_minutes' => 30,
                'keywords' => 'ผู้หญิง, ตรวจภายใน, แพทย์หญิง, for lady, women',
                'terms' => null,
                'effective_from' => $today,
                'effective_until' => null,
            ],
            [
                'code' => 'MEN-001',
                'category' => 'for-men',
                'name_th' => 'ตรวจสุขภาพทางเพศชาย + ปรึกษาแพทย์',
                'name_en' => 'Men Health Consultation',
                'description_th' => 'ตรวจและให้คำปรึกษาสุขภาพเพศชายโดยแพทย์ เป็นความลับ',
                'price' => 1200,
                'sale_price' => 990,
                'duration_minutes' => 30,
                'keywords' => 'ผู้ชาย, สุขภาพเพศชาย, for men, men',
                'terms' => 'โปรโมชั่นถึงสิ้นเดือนนี้',
                'effective_from' => $today,
                'effective_until' => $endOfMonth,
            ],
            [
                'code' => 'CARE-001',
                'category' => 'we-care',
                'name_th' => 'วิตามินผิวใส IV Drip',
                'name_en' => 'Brightening IV Drip',
                'description_th' => 'ดริปวิตามินบำรุงผิวและฟื้นฟูร่างกาย ใช้เวลาประมาณ 45 นาที',
                'price' => 1900,
                'sale_price' => 1500,
                'duration_minutes' => 45,
                'keywords' => 'ดริป, วิตามิน, iv drip, ผิวใส, ดริปวิตามิน',
                'terms' => 'โปรโมชั่นถึงสิ้นเดือนนี้ ต้องประเมินสุขภาพก่อนรับบริการ',
                'effective_from' => $today,
                'effective_until' => $endOfMonth,
            ],
            [
                'code' => 'CARE-002',
                'category' => 'we-care',
                'name_th' => 'แพ็กเกจตรวจสุขภาพพื้นฐาน',
                'name_en' => 'Basic Health Checkup',
                'description_th' => 'ตรวจเลือด ความดัน น้ำตาล ไขมัน พร้อมแพทย์อ่านผล',
                'price' => 1990,
                'sale_price' => null,
                'duration_minutes' => 60,
                'keywords' => 'ตรวจสุขภาพ, ตรวจเลือด, health check, checkup',
                'terms' => 'งดน้ำและอาหาร 8-10 ชั่วโมงก่อนตรวจ',
                'effective_from' => $today,
                'effective_until' => null,
            ],
            [
                'code' => 'SRG-001',
                'category' => 'minor-surgery-aesthetics',
                'name_th' => 'ผ่าฝี / เย็บแผล / ตัดไฝ',
                'name_en' => 'Minor Surgery',
                'description_th' => 'หัตถการศัลยกรรมเล็กโดยแพทย์ ราคาเริ่มต้น ประเมินหน้างานตามขนาดและตำแหน่ง',
                'price' => 1500,
                'sale_price' => null,
                'duration_minutes' => 30,
                'keywords' => 'ผ่าฝี, ตัดไฝ, เย็บแผล, ไฝ, ศัลยกรรมเล็ก, minor surgery',
                'terms' => 'ราคาเริ่มต้น ขึ้นอยู่กับการประเมินของแพทย์',
                'effective_from' => $today,
                'effective_until' => null,
            ],
            [
                'code' => 'VAX-001',
                'category' => 'we-care',
                'name_th' => 'วัคซีนไข้หวัดใหญ่ 4 สายพันธุ์',
                'name_en' => 'Influenza Vaccine (4 strains)',
                'description_th' => 'วัคซีนไข้หวัดใหญ่ 4 สายพันธุ์ รวมค่าบริการฉีดแล้ว',
                'price' => 890,
                'sale_price' => 690,
                'duration_minutes' => 15,
                'keywords' => 'วัคซีน, ไข้หวัดใหญ่, vaccine, flu',
                'terms' => 'โปรโมชั่นถึงสิ้นเดือนนี้',
                'effective_from' => $today,
                'effective_until' => $endOfMonth,
            ],
        ];

        foreach ($packages as $package) {
            ServicePackage::updateOrCreate(
                ['code' => $package['code']],
                [
                    'category_id' => $categoryIds->get($package['category']),
                    'name_th' => $package['name_th'],
                    'name_en' => $package['name_en'],
                    'description_th' => $package['description_th'],
                    'price' => $package['price'],
                    'sale_price' => $package['sale_price'],
                    'currency' => 'THB',
                    'duration_minutes' => $package['duration_minutes'],
                    'terms' => $package['terms'],
                    'keywords' => $package['keywords'],
                    'is_active' => true,
                    'is_published' => true,
                    'effective_from' => $package['effective_from'],
                    'effective_until' => $package['effective_until'],
                ]
            );
        }

        $faqs = [
            [
                'question_th' => 'ต้องจองคิวล่วงหน้าไหม',
                'answer_th' => 'แนะนำให้จองคิวล่วงหน้าอย่างน้อย 1 วันผ่าน LINE @Wmedic หรือโทร 095-696-0966 แต่ walk-in ได้หากคิวว่าง',
                'tags' => 'จอง, นัด, คิว, จองคิว, booking, walk-in',
            ],
            [
                'question_th' => 'มีที่จอดรถไหม',
                'answer_th' => 'มีที่จอดรถหน้าคลินิกจำนวนจำกัด หรือจอดที่อาคารจอดรถ MRT สามแยกบางใหญ่แล้วเดินมาได้',
                'tags' => 'จอดรถ, ที่จอด, parking, รถ',
            ],
            [
                'question_th' => 'ชำระเงินด้วยวิธีไหนได้บ้าง',
                'answer_th' => 'รับเงินสด บัตรเครดิต/เดบิต โอนผ่านธนาคาร และพร้อมเพย์',
                'tags' => 'ชำระ, จ่าย, จ่ายเงิน, บัตรเครดิต, โอน, พร้อมเพย์, payment',
            ],
            [
                'question_th' => 'เลื่อนนัดหรือยกเลิกนัดได้ไหม',
                'answer_th' => 'เลื่อนหรือยกเลิกนัดได้ กรุณาแจ้งล่วงหน้าอย่างน้อย 24 ชั่วโมงผ่าน LINE @Wmedic',
                'tags' => 'เลื่อนนัด, ยกเลิก, เลื่อน, reschedule, cancel',
            ],
            [
                'question_th' => 'ตั้งครรภ์หรือมีโรคประจำตัว รับบริการได้ไหม',
                'answer_th' => 'กรุณาแจ้งเจ้าหน้าที่ก่อนรับบริการทุกครั้ง แพทย์จะประเมินความเหมาะสมเป็นรายบุคคล',
                'tags' => 'ตั้งครรภ์, ท้อง, โรคประจำตัว, แพ้ยา, pregnant',
            ],
            [
                'question_th' => 'ทำเลเซอร์แล้วต้องดูแลตัวเองยังไง',
                'answer_th' => 'หลังทำเลเซอร์ควรงดแดดจัด 1 สัปดาห์ ทาครีมกันแดด SPF50+ และงดสครับผิว 3-5 วัน',
                'tags' => 'ดูแลหลังทำ, หลังเลเซอร์, กันแดด, aftercare',
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question_th' => $faq['question_th']],
                [
                    'answer_th' => $faq['answer_th'],
                    'category' => 'ทั่วไป',
                    'tags' => $faq['tags'],
                    'is_active' => true,
                ]
            );
        }

        KnowledgeEntry::updateOrCreate(
            ['title' => 'โปรโมชั่นประจำเดือนนี้'],
            [
                'body' => implode("\n", [
                    'โปรโมชั่นที่กำลังจัดอยู่ (ถึงสิ้นเดือนนี้):',
                    '- เลเซอร์หน้าใส Q-Switch ลดเหลือ 990 บาท (ปกติ 1,500 บาท)',
                    '- เลเซอร์กำจัดขนรักแร้ คอร์ส 5 ครั้ง ลดเหลือ 2,990 บาท (ปกติ 3,990 บาท)',
                    '- วิตามินผิวใส IV Drip ลดเหลือ 1,500 บาท (ปกติ 1,900 บาท)',
                    '- วัคซีนไข้หวัดใหญ่ 4 สายพันธุ์ ลดเหลือ 690 บาท (ปกติ 890 บาท)',
                    '- ตรวจสุขภาพเพศชาย ลดเหลือ 990 บาท (ปกติ 1,200 บาท)',
                ]),
                'type' => 'promotion',
                'category' => 'โปรโมชั่น',
                'tags' => 'โปรโมชั่น, โปร, ส่วนลด, ลดราคา, promotion',
                'source_url' => null,
                'version' => 1,
                'is_active' => true,
                'reviewed_at' => now(),
            ]
        );
    }
}
