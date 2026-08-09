<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->boolean('is_protected')->default(false)->after('abilities');
        });

        DB::table('api_tokens')
            ->get(['id', 'abilities'])
            ->each(function (object $token): void {
                $abilities = json_decode((string) $token->abilities, true) ?: [];

                if (in_array('analytics:write', $abilities, true)) {
                    DB::table('api_tokens')->where('id', $token->id)->update(['is_protected' => true]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->dropColumn('is_protected');
        });
    }
};
