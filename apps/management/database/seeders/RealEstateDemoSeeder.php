<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\KnowledgeEntry;
use App\Models\PackageCategory;
use App\Models\ServicePackage;
use Illuminate\Database\Seeder;

class RealEstateDemoSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['key' => 'bedrooms', 'label_th' => 'ห้องนอน', 'type' => 'number', 'operators' => ['eq', 'gte', 'lte'], 'searchable' => true],
            ['key' => 'bathrooms', 'label_th' => 'ห้องน้ำ', 'type' => 'number', 'operators' => ['eq', 'gte', 'lte'], 'searchable' => true],
            ['key' => 'usable_area_sqm', 'label_th' => 'พื้นที่ใช้สอย ตร.ม.', 'type' => 'number', 'unit' => 'sqm', 'operators' => ['gte', 'lte'], 'searchable' => true],
            ['key' => 'land_area_sqw', 'label_th' => 'พื้นที่ดิน ตร.ว.', 'type' => 'number', 'unit' => 'sqw', 'operators' => ['gte', 'lte'], 'searchable' => true],
            ['key' => 'floor', 'label_th' => 'ชั้น', 'type' => 'number', 'operators' => ['eq', 'gte', 'lte'], 'searchable' => true],
        ];
        $categories = collect([
            ['slug' => 'condo', 'name_th' => 'คอนโดมิเนียม', 'name_en' => 'Condominium'],
            ['slug' => 'house', 'name_th' => 'บ้าน', 'name_en' => 'House'],
            ['slug' => 'land', 'name_th' => 'ที่ดิน', 'name_en' => 'Land'],
            ['slug' => 'commercial', 'name_th' => 'อสังหาริมทรัพย์เพื่อพาณิชย์', 'name_en' => 'Commercial property'],
        ])->mapWithKeys(fn (array $data) => [$data['slug'] => PackageCategory::updateOrCreate(
            ['slug' => $data['slug']], $data + ['attribute_definitions' => $definitions, 'sort_order' => 10, 'is_active' => true],
        )]);

        $items = [
            ['code' => 'DEMO-CONDO-001', 'category_id' => $categories['condo']->id, 'item_type' => 'property', 'transaction_type' => 'sale', 'name_th' => 'คอนโดตัวอย่าง บางนา 2 ห้องนอน', 'description_th' => 'ข้อมูลตัวอย่างสำหรับทดสอบระบบ ไม่ใช่ประกาศขายจริง', 'price' => 3850000, 'location_text' => 'บางนา กรุงเทพมหานคร', 'province' => 'กรุงเทพมหานคร', 'district' => 'บางนา', 'project_name' => 'บางนา เรสซิเดนซ์ (ตัวอย่าง)', 'bedrooms' => 2, 'bathrooms' => 2, 'usable_area_sqm' => 58, 'floor' => 12, 'keywords' => 'คอนโด,บางนา,2 ห้องนอน'],
            ['code' => 'DEMO-CONDO-002', 'category_id' => $categories['condo']->id, 'item_type' => 'property', 'transaction_type' => 'rent', 'name_th' => 'คอนโดตัวอย่าง อ่อนนุช 1 ห้องนอน', 'description_th' => 'ข้อมูลตัวอย่างสำหรับทดสอบระบบ ไม่ใช่ประกาศให้เช่าจริง', 'price' => 22000, 'location_text' => 'อ่อนนุช กรุงเทพมหานคร', 'province' => 'กรุงเทพมหานคร', 'district' => 'สวนหลวง', 'project_name' => 'อ่อนนุช เพลส (ตัวอย่าง)', 'bedrooms' => 1, 'bathrooms' => 1, 'usable_area_sqm' => 36, 'floor' => 8, 'keywords' => 'คอนโด,อ่อนนุช,เช่า'],
            ['code' => 'DEMO-LAND-001', 'category_id' => $categories['land']->id, 'item_type' => 'property', 'transaction_type' => 'sale', 'name_th' => 'ที่ดินตัวอย่าง ใกล้บางนา-ตราด', 'description_th' => 'ข้อมูลตัวอย่างสำหรับทดสอบระบบ ไม่ใช่ที่ดินขายจริง', 'price' => 12500000, 'location_text' => 'บางนา-ตราด สมุทรปราการ', 'province' => 'สมุทรปราการ', 'district' => 'บางพลี', 'land_area_sqw' => 100, 'keywords' => 'ที่ดิน,บางนา,บางพลี'],
            ['code' => 'DEMO-HOUSE-001', 'category_id' => $categories['house']->id, 'item_type' => 'property', 'transaction_type' => 'sale', 'name_th' => 'บ้านเดี่ยวตัวอย่าง ศรีนครินทร์', 'description_th' => 'ข้อมูลตัวอย่างสำหรับทดสอบระบบ ไม่ใช่บ้านขายจริง', 'price' => 7900000, 'location_text' => 'ศรีนครินทร์ สมุทรปราการ', 'province' => 'สมุทรปราการ', 'district' => 'เมืองสมุทรปราการ', 'bedrooms' => 3, 'bathrooms' => 3, 'usable_area_sqm' => 180, 'land_area_sqw' => 56, 'keywords' => 'บ้านเดี่ยว,ศรีนครินทร์,3 ห้องนอน'],
        ];
        foreach ($items as $item) {
            ServicePackage::updateOrCreate(['code' => $item['code']], $item + ['currency' => 'THB', 'availability' => 'available', 'is_active' => true, 'is_published' => true]);
        }

        Faq::updateOrCreate(['question_th' => 'ข้อมูลอสังหาริมทรัพย์ในระบบเป็นข้อมูลจริงหรือไม่'], ['answer_th' => 'ข้อมูลตัวอย่างเริ่มต้นใช้สำหรับทดสอบระบบเท่านั้น กรุณาตรวจสอบรายการจริงกับเจ้าหน้าที่ก่อนตัดสินใจ', 'category' => 'policy', 'tags' => 'ตัวอย่าง,อสังหาริมทรัพย์', 'is_active' => true]);
        KnowledgeEntry::updateOrCreate(['title' => 'นโยบายการตอบข้อมูลรายการ'], ['body' => 'ให้ยืนยันความพร้อมและราคาเฉพาะจากรายการที่เผยแพร่ในระบบ หากต้องการต่อรอง นัดหมาย หรือชำระเงิน ให้ส่งต่อทีมเจ้าหน้าที่', 'type' => 'policy', 'category' => 'sales', 'tags' => 'ราคา,availability,handoff', 'version' => 1, 'is_active' => true, 'reviewed_at' => now()]);
    }
}
