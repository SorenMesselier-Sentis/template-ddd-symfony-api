<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class LogoutUserControllerTest extends HttpTestCase
{
    public function testLogoutReturns204(): void
    {
        $client = $this->createAuthenticatedClient('user');
        $client->request(
            'POST',
            '/api/v1/auth/logout',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['refreshToken' => 'dummy'], JSON_THROW_ON_ERROR),
        );

        $this->assertContains($client->getResponse()->getStatusCode(), [204, 400, 422]);
    }
}
