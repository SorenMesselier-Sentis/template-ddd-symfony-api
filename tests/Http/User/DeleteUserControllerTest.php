<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class DeleteUserControllerTest extends HttpTestCase
{
    public function testDeleteUser(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request(
            'POST',
            '/api/v1/users',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'firstName' => 'Tom',
                'lastName' => 'Delete',
                'email' => 'delete-me@example.com',
                'password' => 'secret1234',
            ], JSON_THROW_ON_ERROR),
        );
        $created = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $client->request('DELETE', '/api/v1/users/'.$created['data']['id']);
        $this->assertSame(204, $client->getResponse()->getStatusCode());
    }
}
