<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('package_categories')->nullOnDelete();
            $table->string('code', 60)->nullable();
            $table->string('name_th');
            $table->string('name_en')->nullable();
            $table->text('description_th')->nullable();
            $table->text('description_en')->nullable();
            // Structured facts: price columns are the source of truth for AI answers.
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('THB');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->text('terms')->nullable();
            $table->text('keywords')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false);
            // Effective date window used to gate AI answers.
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_published']);
            $table->index('category_id');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
