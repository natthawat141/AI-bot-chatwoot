<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\PackageCategory;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlexMessageApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(): array
    {
        ['plainText' => $plainText] = ApiToken::issue('test-token', ['read']);

        return ['Authorization' => 'Bearer '.$plainText];
    }

    public function test_it_returns_property_flex_bubble(): void
    {
        $category = PackageCategory::create([
            'slug' => 'condo',
            'name_th' => 'คอนโดมิเนียม',
            'name_en' => 'Condominium',
            'is_active' => true,
        ]);

        $package = ServicePackage::create([
            'code' => 'TEST-001',
            'name_th' => 'The Base บางนา',
            'category_id' => $category->id,
            'item_type' => 'property',
            'transaction_type' => 'sale',
            'availability' => 'available',
            'price' => 2500000,
            'is_active' => true,
            'is_published' => true,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'usable_area_sqm' => 32,
        ]);

        $res = $this->getJson("/api/v1/flex/catalog/{$package->id}", $this->auth())
            ->assertOk();

        $res->assertJsonPath('type', 'flex');
        $res->assertJsonPath('contents.type', 'bubble');
        $res->assertJsonPath('contents.body.contents.0.text', '฿2,500,000');
    }

    public function test_it_returns_carousel_flex(): void
    {
        $category = PackageCategory::create([
            'slug' => 'condo',
            'name_th' => 'คอนโดมิเนียม',
            'name_en' => 'Condominium',
            'is_active' => true,
        ]);

        ServicePackage::create([
            'code' => 'TEST-001',
            'name_th' => 'The Base บางนา',
            'category_id' => $category->id,
            'item_type' => 'property',
            'transaction_type' => 'sale',
            'availability' => 'available',
            'price' => 2500000,
            'is_active' => true,
            'is_published' => true,
        ]);

        $res = $this->getJson('/api/v1/flex/carousel?category_slug=condo', $this->auth())
            ->assertOk();

        $res->assertJsonPath('type', 'flex');
        $res->assertJsonPath('contents.type', 'carousel');
    }
}
