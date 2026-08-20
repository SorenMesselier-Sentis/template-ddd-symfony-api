<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class GetRealtimeSubscriberTokenControllerTest extends HttpTestCase
{
    public function testItSetsAMercureAuthorizationCookieScopedToTheCurrentUser(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $client->request('GET', '/api/v1/users/me/realtime-token');

        $this->assertSame(204, $client->getResponse()->getStatusCode());
        $cookie = $client->getResponse()->headers->getCookies()[0] ?? null;
        $this->assertNotNull($cookie);
        $this->assertSame('mercureAuthorization', $cookie->getName());
        $this->assertNotEmpty($cookie->getValue());
    }

    public function testItRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/users/me/realtime-token');

        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}
