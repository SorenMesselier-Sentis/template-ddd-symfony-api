<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class GetMeControllerTest extends HttpTestCase
{
    public function testGetMeReturnsCurrentUser(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $client->request('GET', '/api/v1/users/me');

        $this->assertJsonEnvelope($client->getResponse(), 200);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('jane', $payload['data']['first_name']);
    }
}
