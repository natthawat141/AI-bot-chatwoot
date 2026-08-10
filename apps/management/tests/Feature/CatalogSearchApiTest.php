<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\PackageCategory;
use App\Models\ServicePackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSearchApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(): array
    {
        ['plainText' => $plainText] = ApiToken::issue('catalog-test');
        return ['Authorization' => 'Bearer '.$plainText];
    }

    public function test_it_returns_only_available_published_catalog_items(): void
    {
        $category = PackageCategory::factory()->create(['slug' => 'condo']);
        $visible = ServicePackage::factory()->create(['category_id' => $category->id, 'item_type' => 'property', 'transaction_type' => 'sale', 'availability' => 'available', 'bedrooms' => 2, 'price' => 3900000]);
        ServicePackage::factory()->create(['category_id' => $category->id, 'availability' => 'reserved']);
        ServicePackage::factory()->unpublished()->create(['category_id' => $category->id, 'availability' => 'available']);

        $this->postJson('/api/v1/catalog/search', ['category_slug' => 'condo', 'price' => ['max' => 4000000], 'attributes' => ['bedrooms' => ['gte' => 2]]], $this->auth())
            ->assertOk()->assertJsonPath('meta.count', 1)->assertJsonPath('data.0.id', $visible->id);
    }

    public function test_it_rejects_unknown_attribute_filters_before_querying(): void
    {
        $this->postJson('/api/v1/catalog/search', ['attributes' => ['drop_table' => ['gte' => 1]]], $this->auth())
            ->assertStatus(422);
    }

    public function test_zero_results_are_explicit_and_do_not_relax_filters(): void
    {
        ServicePackage::factory()->create(['transaction_type' => 'sale', 'availability' => 'available', 'price' => 9000000, 'sale_price' => null]);

        $this->postJson('/api/v1/catalog/search', ['transaction_type' => 'sale', 'price' => ['max' => 1000000]], $this->auth())
            ->assertOk()->assertJsonPath('meta.count', 0)->assertJsonPath('data', []);
    }

    public function test_detail_hides_unavailable_catalog_items(): void
    {
        $item = ServicePackage::factory()->create(['availability' => 'unavailable']);

        $this->getJson('/api/v1/catalog/'.$item->id, $this->auth())->assertNotFound();
    }
}
