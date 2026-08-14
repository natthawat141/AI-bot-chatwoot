<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $table = 'business_profile';

    protected $fillable = [
        'business_name',
        'business_description',
        'services_offered',
        'service_areas',
        'business_hours',
        'contact_channels',
        'conversation_tone',
        'always_escalate_topics',
    ];

    /**
     * Singleton accessor: always the record with id 1, created on first access
     * with sensible defaults so the AI service and admin form always have data.
     */
    public static function current(): self
    {
        return self::firstOrCreate(['id' => 1], [
            'business_name' => 'ธุรกิจของฉัน',
            'business_description' => 'กรุณากรอกรายละเอียดธุรกิจในหน้าตั้งค่าโปรไฟล์',
        ]);
    }
}
