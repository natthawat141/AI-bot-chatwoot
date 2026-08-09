<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_categories', function (Blueprint $table) {
            $table->json('attribute_definitions')->nullable()->after('description');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->string('item_type', 40)->default('service')->after('category_id');
            $table->string('transaction_type', 20)->nullable()->after('currency');
            $table->string('availability', 20)->default('available')->after('transaction_type');
            $table->string('location_text', 255)->nullable()->after('keywords');
            $table->string('province', 100)->nullable()->after('location_text');
            $table->string('district', 100)->nullable()->after('province');
            $table->string('subdistrict', 100)->nullable()->after('district');
            $table->string('project_name', 255)->nullable()->after('subdistrict');
            $table->unsignedTinyInteger('bedrooms')->nullable()->after('project_name');
            $table->unsignedTinyInteger('bathrooms')->nullable()->after('bedrooms');
            $table->decimal('usable_area_sqm', 10, 2)->nullable()->after('bathrooms');
            $table->decimal('land_area_sqw', 10, 2)->nullable()->after('usable_area_sqm');
            $table->unsignedSmallInteger('floor')->nullable()->after('land_area_sqw');
            $table->json('attributes')->nullable()->after('floor');
            $table->index(['item_type', 'transaction_type', 'availability'], 'packages_catalog_state_idx');
            $table->index(['province', 'district'], 'packages_location_idx');
            $table->index(['price', 'sale_price'], 'packages_price_idx');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropIndex('packages_catalog_state_idx');
            $table->dropIndex('packages_location_idx');
            $table->dropIndex('packages_price_idx');
            $table->dropColumn(['item_type', 'transaction_type', 'availability', 'location_text', 'province', 'district', 'subdistrict', 'project_name', 'bedrooms', 'bathrooms', 'usable_area_sqm', 'land_area_sqw', 'floor', 'attributes']);
        });
        Schema::table('package_categories', fn (Blueprint $table) => $table->dropColumn('attribute_definitions'));
    }
};
