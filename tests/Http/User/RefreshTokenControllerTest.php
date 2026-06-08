<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class RefreshTokenControllerTest extends HttpTestCase
{
    public function testRefreshWithInvalidTokenReturns401(): void
    {
        $client = static::createClient();
        $this->resetDatabase();
        $client->request(
            'POST',
            '/api/v1/auth/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['refresh_token' => 'invalid'], JSON_THROW_ON_ERROR),
        );

        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}
