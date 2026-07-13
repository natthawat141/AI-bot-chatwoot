<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_records', function (Blueprint $table) {
            $table->unsignedInteger('rows_skipped')->default(0)->after('rows_failed');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->unique('code', 'packages_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropUnique('packages_code_unique');
        });

        Schema::table('import_records', function (Blueprint $table) {
            $table->dropColumn('rows_skipped');
        });
    }
};
