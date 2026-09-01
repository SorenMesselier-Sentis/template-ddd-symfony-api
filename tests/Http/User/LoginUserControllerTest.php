<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class LoginUserControllerTest extends HttpTestCase
{
    public function testLoginReturnsTokenEnvelope(): void
    {
        $client = static::createClient();
        $this->resetDatabase();
        $client->request(
            'POST',
            '/api/v1/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'john.doe@example.com', 'password' => 'secret1234'], JSON_THROW_ON_ERROR),
        );

        $this->assertJsonEnvelope($client->getResponse(), 200);
    }

    /**
     * Regression test: two logins for the same user issued within the same second used to
     * produce byte-identical refresh token JWTs (only `sub`/`type` claims, both constant, plus
     * Lexik's own second-precision iat/exp) — the second login's persistNewAccessToken-style
     * save collided with refresh_tokens' unique `token` column and 500'd. See JwtTokenService's
     * `jti` claim.
     */
    public function testConsecutiveLoginsForTheSameUserDoNotCollideOnTheRefreshToken(): void
    {
        $client = static::createClient();
        $this->resetDatabase();

        $credentials = json_encode(['email' => 'john.doe@example.com', 'password' => 'secret1234'], JSON_THROW_ON_ERROR);

        $client->request('POST', '/api/v1/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: $credentials);
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $first = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $client->request('POST', '/api/v1/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: $credentials);
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $second = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotSame($first['data']['refresh_token'], $second['data']['refresh_token']);
    }
}
