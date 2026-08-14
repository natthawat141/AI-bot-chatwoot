<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profile', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->text('business_description'); // 1-3 sentences: what the business does
            $table->text('services_offered')->nullable(); // e.g. ขาย/เช่า/ฝากขาย
            $table->text('service_areas')->nullable(); // ทำเลที่ให้บริการ
            $table->string('business_hours')->nullable();
            $table->text('contact_channels')->nullable();
            $table->string('conversation_tone')->nullable(); // e.g. "เป็นกันเอง, มืออาชีพ"
            $table->text('always_escalate_topics')->nullable(); // topics that must always go to a human
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profile');
    }
};
