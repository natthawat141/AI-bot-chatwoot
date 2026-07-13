<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('resource', 40); // packages | faqs | knowledge
            $table->string('filename');
            $table->string('status', 20)->default('completed'); // completed | failed
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->json('errors')->nullable(); // per-row validation errors
            $table->timestamps();

            $table->index(['resource', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_records');
    }
};
