<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class GetUsersControllerTest extends HttpTestCase
{
    public function testListUsersAsAdmin(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', '/api/v1/users?page=1&limit=10');

        $response = $client->getResponse();
        $this->assertJsonEnvelope($response, 200);

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('meta', $payload);
        $this->assertArrayHasKey('links', $payload);
        $this->assertArrayHasKey('page', $payload['meta']);
        $this->assertArrayHasKey('limit', $payload['meta']);
        $this->assertArrayHasKey('total_items', $payload['meta']);
        $this->assertArrayHasKey('total_pages', $payload['meta']);
        $this->assertArrayHasKey('has_next', $payload['meta']);
        $this->assertArrayHasKey('has_previous', $payload['meta']);
        $this->assertArrayHasKey('self', $payload['links']);
        $this->assertArrayHasKey('next', $payload['links']);
        $this->assertArrayHasKey('previous', $payload['links']);
        $this->assertStringStartsWith('/v1/users?', $payload['links']['self']);
    }

    public function testUserRoleCannotCreateUser(): void
    {
        $client = $this->createAuthenticatedClient('user');
        $client->request(
            'POST',
            '/api/v1/users',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'firstName' => 'X',
                'lastName' => 'Y',
                'email' => 'x@example.com',
                'password' => 'secret1234',
            ], JSON_THROW_ON_ERROR),
        );

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $this->assertSame('insufficient_privileges', $payload['error']['code']);
    }
}
