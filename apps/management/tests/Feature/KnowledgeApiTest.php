<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Faq;
use App\Models\KnowledgeEntry;
use App\Models\ServicePackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(): array
    {
        ['plainText' => $plainText] = ApiToken::issue('test');

        return ['Authorization' => 'Bearer '.$plainText];
    }

    public function test_it_rejects_requests_without_a_token(): void
    {
        $this->getJson('/api/v1/knowledge')->assertStatus(401);
    }

    public function test_it_rejects_an_invalid_token(): void
    {
        $this->getJson('/api/v1/knowledge', ['Authorization' => 'Bearer not-a-real-token'])
            ->assertStatus(401);
    }

    public function test_it_rejects_a_revoked_token(): void
    {
        ['token' => $token, 'plainText' => $plainText] = ApiToken::issue('revoked');
        $token->update(['revoked_at' => now()]);

        $this->getJson('/api/v1/knowledge', ['Authorization' => 'Bearer '.$plainText])
            ->assertStatus(401);
    }

    public function test_it_rejects_an_expired_token(): void
    {
        ['plainText' => $plainText] = ApiToken::issue('expired', ['read'], now()->subDay());

        $this->getJson('/api/v1/knowledge', ['Authorization' => 'Bearer '.$plainText])
            ->assertStatus(401);
    }

    public function test_a_valid_token_updates_last_used_at(): void
    {
        ['token' => $token, 'plainText' => $plainText] = ApiToken::issue('used');
        $this->assertNull($token->last_used_at);

        $this->getJson('/api/v1/knowledge', ['Authorization' => 'Bearer '.$plainText])
            ->assertOk();

        $this->assertNotNull($token->fresh()->last_used_at);
    }

    public function test_packages_must_be_active_published_and_effective(): void
    {
        $visible = ServicePackage::factory()->create();
        ServicePackage::factory()->inactive()->create();
        ServicePackage::factory()->unpublished()->create();
        ServicePackage::factory()->create([
            'effective_from' => now()->addWeek()->toDateString(),
        ]);
        ServicePackage::factory()->create([
            'effective_until' => now()->subWeek()->toDateString(),
        ]);

        $response = $this->getJson('/api/v1/packages', $this->auth())->assertOk();

        $response->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.id', $visible->id);
    }

    public function test_updated_since_filter_excludes_older_records(): void
    {
        $old = Faq::factory()->create();
        $old->timestamps = false;
        $old->forceFill(['updated_at' => now()->subYear()])->saveQuietly();
        $recent = Faq::factory()->create();

        $response = $this->getJson('/api/v1/faqs?updated_since='.urlencode(now()->subMonth()->toIso8601String()), $this->auth())
            ->assertOk();

        $response->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.id', $recent->id);
    }

    public function test_knowledge_returns_only_active_entries(): void
    {
        $active = KnowledgeEntry::factory()->create();
        KnowledgeEntry::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/knowledge', $this->auth())->assertOk();

        $response->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.id', $active->id);
    }

    public function test_faq_search_matches_question_and_does_not_return_unrelated_rows(): void
    {
        $match = Faq::factory()->create([
            'question_th' => 'วิธีชำระเงินสำหรับคอนโด',
            'answer_th' => 'โอนผ่านบัญชีบริษัท',
        ]);
        Faq::factory()->create([
            'question_th' => 'เงื่อนไขการเช่า',
            'answer_th' => 'สัญญาขั้นต่ำหนึ่งปี',
        ]);

        $response = $this->getJson('/api/v1/faqs?q='.urlencode('ชำระเงิน').'&limit=5', $this->auth())
            ->assertOk();

        $response->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_faq_search_matches_a_differently_phrased_thai_question(): void
    {
        // Thai has no spaces between words, so a shorter, differently worded
        // question must still match a longer FAQ title covering the same
        // topic -- a single whole-string LIKE previously required a near
        // character-for-character substring match and missed this.
        $match = Faq::factory()->create([
            'question_th' => 'ค่าธรรมเนียมการโอนกรรมสิทธิ์ใครเป็นคนจ่าย',
            'answer_th' => 'แบ่งจ่ายฝ่ายละครึ่งตามปกติ',
        ]);
        Faq::factory()->create([
            'question_th' => 'เงื่อนไขการเช่า',
            'answer_th' => 'สัญญาขั้นต่ำหนึ่งปี',
        ]);

        $response = $this->getJson('/api/v1/faqs?q='.urlencode('ค่าโอนใครจ่าย').'&limit=5', $this->auth())
            ->assertOk();

        $response->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_knowledge_search_matches_body_and_tags(): void
    {
        $match = KnowledgeEntry::factory()->create([
            'body' => 'นโยบายคืนเงินและการตรวจสอบรายการโอน',
            'tags' => 'ชำระเงิน,คืนเงิน',
        ]);
        KnowledgeEntry::factory()->create(['body' => 'ข้อมูลทำเลบางนา', 'tags' => 'ทำเล']);

        $response = $this->getJson('/api/v1/knowledge?q='.urlencode('คืนเงิน').'&limit=5', $this->auth())
            ->assertOk();

        $response->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_meta_reports_active_counts_and_schema_version(): void
    {
        ServicePackage::factory()->create();
        Faq::factory()->create();
        KnowledgeEntry::factory()->create();

        $response = $this->getJson('/api/v1/meta', $this->auth())->assertOk();

        $response->assertJsonPath('data.schema_version', '1.0')
            ->assertJsonPath('data.counts.packages', 1)
            ->assertJsonPath('data.counts.faqs', 1)
            ->assertJsonPath('data.counts.knowledge', 1);
    }
}
