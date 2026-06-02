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
}
