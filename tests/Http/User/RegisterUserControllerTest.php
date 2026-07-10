<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class RegisterUserControllerTest extends HttpTestCase
{
    public function testRegisterCreatesUser(): void
    {
        $client = static::createClient();
        $this->resetDatabase();
        $client->request(
            'POST',
            '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'firstName' => 'Alice',
                'lastName' => 'Wonder',
                'email' => 'alice@example.com',
                'password' => 'secret1234',
            ], JSON_THROW_ON_ERROR),
        );

        $this->assertJsonEnvelope($client->getResponse(), 201);
    }
}
