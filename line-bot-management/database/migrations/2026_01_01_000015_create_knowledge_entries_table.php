<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_entries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('body'); // plaintext / Markdown
            $table->string('type', 50)->default('general'); // general | policy | promotion | procedure ...
            $table->string('category')->nullable();
            $table->string('tags')->nullable(); // comma-separated
            $table->string('source_url')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'type']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_entries');
    }
};
