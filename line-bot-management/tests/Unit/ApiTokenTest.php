<?php

namespace Tests\Unit;

use App\Models\ApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_stores_only_a_hash_never_plaintext(): void
    {
        ['token' => $token, 'plainText' => $plainText] = ApiToken::issue('bot');

        $this->assertNotEmpty($plainText);
        $this->assertNotSame($plainText, $token->token_hash);
        $this->assertSame(hash('sha256', $plainText), $token->token_hash);
        // The stored prefix is the non-secret identifier embedded in the plaintext.
        $this->assertStringStartsWith($token->prefix, $plainText);
    }

    public function test_find_valid_resolves_a_good_token(): void
    {
        ['token' => $token, 'plainText' => $plainText] = ApiToken::issue('bot');

        $this->assertSame($token->id, ApiToken::findValid($plainText)?->id);
    }

    public function test_find_valid_rejects_unknown_token(): void
    {
        ApiToken::issue('bot');

        $this->assertNull(ApiToken::findValid('lk_deadbeef.not-real'));
    }

    public function test_find_valid_rejects_revoked_token(): void
    {
        ['token' => $token, 'plainText' => $plainText] = ApiToken::issue('bot');
        $token->update(['revoked_at' => now()]);

        $this->assertNull(ApiToken::findValid($plainText));
    }

    public function test_find_valid_rejects_expired_token(): void
    {
        ['plainText' => $plainText] = ApiToken::issue('bot', ['read'], now()->subMinute());

        $this->assertNull(ApiToken::findValid($plainText));
    }
}
