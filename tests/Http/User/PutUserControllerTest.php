<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class PutUserControllerTest extends HttpTestCase
{
    public function testReplaceUser(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', '/api/v1/users');
        $list = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $user = $list['data'][0];

        $client->request(
            'PUT',
            '/api/v1/users/'.$user['id'],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'firstName' => $user['first_name'],
                'lastName' => $user['last_name'],
                'email' => $user['email'],
                'password' => 'secret1234',
            ], JSON_THROW_ON_ERROR),
        );

        $this->assertSame(204, $client->getResponse()->getStatusCode());
    }
}
