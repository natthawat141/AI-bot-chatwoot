<?php

namespace Database\Seeders;

use App\Models\KnowledgeEntry;
use App\Models\PackageCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Verified answer content for W+ Medic Clinic.
 *
 * All facts below were taken from the official site on 2026-07-13:
 *   https://www.wmedicclinic.com/  and  https://www.wmedicclinic.com/contact-3
 *
 * NOTE: The official page currently shows CONFLICTING opening hours, so hours are
 * intentionally left unset with a review-needed note. Do not present hours as fact.
 * No prices or medical claims are invented — only clearly named service categories
 * from the homepage are seeded.
 */
class WMedicClinicSeeder extends Seeder
{
    private const SOURCE_URL = 'https://www.wmedicclinic.com/';

    public function run(): void
    {
        // Representative, clearly named service categories from the homepage.
        // Descriptions kept generic; no medical claims or prices.
        $categories = [
            ['name_en' => 'Laser & Skin', 'name_th' => 'เลเซอร์และผิวพรรณ'],
            ['name_en' => 'For Lady', 'name_th' => 'สำหรับผู้หญิง'],
            ['name_en' => 'For Men', 'name_th' => 'สำหรับผู้ชาย'],
            ['name_en' => 'We Care', 'name_th' => 'ดูแลสุขภาพผิว'],
            ['name_en' => 'Minor Surgery & Aesthetics', 'name_th' => 'ศัลยกรรมเล็กและความงาม'],
        ];

        foreach ($categories as $i => $cat) {
            PackageCategory::updateOrCreate(
                ['slug' => Str::slug($cat['name_en'])],
                [
                    'name_th' => $cat['name_th'],
                    'name_en' => $cat['name_en'],
                    'description' => null,
                    'sort_order' => $i,
                    'is_active' => true,
                ]
            );
        }

        // Label the official source and the hours caveat as a reviewable knowledge entry.
        KnowledgeEntry::updateOrCreate(
            ['title' => 'W+ Medic Clinic (บางใหญ่) — ข้อมูลติดต่อทางการ'],
            [
                'body' => implode("\n", [
                    '# W+ Medic Clinic / ดับบลิว พลัส เมดิก คลินิกเวชกรรม',
                    '',
                    '- สาขา: บางใหญ่ (ติดสถานีรถไฟฟ้า MRT สามแยกบางใหญ่)',
                    '- ที่อยู่: 56/19-20 หมู่ 15 ถนนรัตนาธิเบศร์ ต.บางรักพัฒนา อ.บางบัวทอง จ.นนทบุรี 11110',
                    '- Call center: 095-696-0966 | สาขา: 02-126-0408 | LINE: @Wmedic',
                    '- แผนที่: https://maps.app.goo.gl/473PDwRaZs7hzeQR7',
                    '',
                    '> หมายเหตุ: เวลาทำการบนเว็บไซต์ทางการยังไม่ตรงกัน (รอตรวจสอบ) — อย่าเผยแพร่เป็นข้อเท็จจริงจนกว่าจะยืนยัน',
                ]),
                'type' => 'reference',
                'category' => 'ข้อมูลติดต่อ',
                'tags' => 'wmedic,บางใหญ่,ติดต่อ,contact',
                'source_url' => 'https://www.wmedicclinic.com/contact-3',
                'version' => 1,
                'is_active' => true,
                'reviewed_at' => null, // hours still need verification
            ]
        );
    }
}
