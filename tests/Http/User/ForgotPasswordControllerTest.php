<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class ForgotPasswordControllerTest extends HttpTestCase
{
    public function testForgotPasswordReturnsNoContent(): void
    {
        $client = static::createClient();
        $this->resetDatabase();
        $client->request(
            'POST',
            '/api/v1/auth/forgot-password',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'john.doe@example.com'], JSON_THROW_ON_ERROR),
        );

        $this->assertSame(204, $client->getResponse()->getStatusCode());
    }
}
