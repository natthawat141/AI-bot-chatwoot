<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_issue_a_token_and_sees_plaintext_once(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/api-tokens', ['name' => 'line-bot'])
            ->assertRedirect('/admin/api-tokens')
            ->assertSessionHas('plainToken');

        $this->assertDatabaseHas('api_tokens', ['name' => 'line-bot']);
        // Only a hash is persisted.
        $this->assertNull(ApiToken::first()->getAttribute('token'));
    }

    public function test_admin_can_revoke_a_token(): void
    {
        ['token' => $token] = ApiToken::issue('line-bot');

        $this->actingAs($this->admin())
            ->delete("/admin/api-tokens/{$token->id}")
            ->assertRedirect();

        $this->assertNotNull($token->fresh()->revoked_at);
    }

    public function test_admin_cannot_revoke_a_protected_system_token_from_the_web(): void
    {
        ['token' => $token] = ApiToken::issue('line-bot-agent', ['read', 'analytics:write'], null, true);

        $this->actingAs($this->admin())
            ->delete("/admin/api-tokens/{$token->id}")
            ->assertRedirect('/admin/api-tokens')
            ->assertSessionHas('error');

        $this->assertNull($token->fresh()->revoked_at);
    }

    public function test_non_admin_cannot_manage_tokens(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/api-tokens')->assertForbidden();
    }
}
