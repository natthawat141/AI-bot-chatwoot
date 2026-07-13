<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the read-only knowledge API with a revocable, SHA-256 hashed bearer token.
 * The plaintext is never stored; we look up its hash via ApiToken::findValid().
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, ?string $requiredAbility = null): Response
    {
        $plainText = $this->bearerToken($request);

        $token = $plainText !== null ? ApiToken::findValid($plainText) : null;

        if ($token === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($requiredAbility !== null) {
            $abilities = $token->abilities ?? [];
            if (! in_array('*', $abilities, true) && ! in_array($requiredAbility, $abilities, true)) {
                return response()->json(['message' => 'This token does not have the required ability.'], 403);
            }
        }

        // Record usage without touching updated_at or firing model events.
        $token->last_used_at = now();
        $token->saveQuietly();

        $request->attributes->set('api_token', $token);

        return $next($request);
    }

    /**
     * Extract the bearer token from the Authorization header ("Bearer xxx").
     */
    private function bearerToken(Request $request): ?string
    {
        $header = (string) $request->header('Authorization', '');

        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches) === 1) {
            $value = trim($matches[1]);

            return $value !== '' ? $value : null;
        }

        return null;
    }
}
