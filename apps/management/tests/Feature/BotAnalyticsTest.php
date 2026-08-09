<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\BotInteraction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BotAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'event_id' => 'evt-test-1',
            'message_id' => 'msg-test-1',
            'user_hash' => hash('sha256', 'Utest'),
            'question' => 'มีแพ็กเกจอะไรบ้าง',
            'answer' => 'มีข้อมูลตัวอย่างดังนี้',
            'response_type' => 'ai',
            'status' => 'answered',
            'model' => 'test-model',
            'duration_ms' => 320,
        ];
    }

    public function test_analytics_endpoint_requires_write_ability(): void
    {
        ['plainText' => $readToken] = ApiToken::issue('read-only', ['read']);

        $this->postJson('/api/v1/interactions', $this->payload(), [
            'Authorization' => 'Bearer '.$readToken,
        ])->assertForbidden();
    }

    public function test_bot_can_record_an_interaction_idempotently(): void
    {
        ['plainText' => $token] = ApiToken::issue('analytics', ['analytics:write']);
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->postJson('/api/v1/interactions', $this->payload(), $headers)->assertCreated();
        $this->postJson('/api/v1/interactions', $this->payload(), $headers)->assertOk();

        $this->assertDatabaseCount('bot_interactions', 1);
        $this->assertDatabaseHas('bot_interactions', [
            'event_id' => 'evt-test-1',
            'question' => 'มีแพ็กเกจอะไรบ้าง',
            'status' => 'answered',
        ]);
    }

    public function test_dashboard_shows_latest_bot_answers(): void
    {
        $user = User::factory()->create();
        BotInteraction::create($this->payload());

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('analytics.total', 1)
                ->where('analytics.today', 1)
                ->where('analytics.successRate', 100)
                ->where('analytics.latest.0.question', 'มีแพ็กเกจอะไรบ้าง')
            );
    }
}
