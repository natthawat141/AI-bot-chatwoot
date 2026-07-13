<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('message_id')->nullable();
            $table->string('user_hash', 64)->nullable()->index();
            $table->text('question');
            $table->text('answer');
            $table->string('response_type', 20)->default('ai');
            $table->string('status', 20)->default('answered');
            $table->string('model', 120)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['response_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_interactions');
    }
};
