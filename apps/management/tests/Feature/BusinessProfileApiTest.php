<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessProfileApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(): array
    {
        ['plainText' => $plainText] = ApiToken::issue('test');

        return ['Authorization' => 'Bearer '.$plainText];
    }

    public function test_it_rejects_requests_without_a_token(): void
    {
        $this->getJson('/api/v1/business-profile')->assertStatus(401);
    }

    public function test_it_rejects_an_invalid_token(): void
    {
        $this->getJson('/api/v1/business-profile', ['Authorization' => 'Bearer not-a-real-token'])
            ->assertStatus(401);
    }

    public function test_a_valid_token_can_read_the_profile(): void
    {
        BusinessProfile::query()->create([
            'business_name' => 'Aion Property',
            'business_description' => 'ให้บริการซื้อขายและเช่าอสังหาริมทรัพย์',
        ]);

        $response = $this->getJson('/api/v1/business-profile', $this->auth())->assertOk();

        $response->assertJsonPath('data.business_name', 'Aion Property')
            ->assertJsonPath('data.business_description', 'ให้บริการซื้อขายและเช่าอสังหาริมทรัพย์')
            ->assertJsonPath('meta.version', '1.0');
    }

    public function test_defaults_exist_even_before_any_admin_edit(): void
    {
        $this->assertDatabaseCount('business_profile', 0);

        $response = $this->getJson('/api/v1/business-profile', $this->auth())->assertOk();

        $response->assertJsonPath('data.business_name', 'ธุรกิจของฉัน');
        $this->assertDatabaseCount('business_profile', 1);
    }
}
