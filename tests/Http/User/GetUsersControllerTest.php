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

    public function testListUsersWithCursorPagination(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', '/api/v1/users?pagination=cursor&limit=10');

        $response = $client->getResponse();
        $this->assertJsonEnvelope($response, 200);

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('meta', $payload);
        $this->assertArrayHasKey('limit', $payload['meta']);
        $this->assertArrayHasKey('has_more', $payload['meta']);
        $this->assertArrayHasKey('next_cursor', $payload['meta']);
        $this->assertArrayNotHasKey('page', $payload['meta']);
        $this->assertArrayHasKey('self', $payload['links']);
        $this->assertArrayNotHasKey('previous', $payload['links']);
        $this->assertStringStartsWith('/v1/users?', $payload['links']['self']);
    }

    public function testListUsersWithCursorPaginationWalksAllPagesWithoutDuplicates(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $seenIds = [];
        $cursor = null;

        do {
            $query = 'pagination=cursor&limit=1'.(null !== $cursor ? '&cursor='.urlencode($cursor) : '');
            $client->request('GET', '/api/v1/users?'.$query);
            $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

            foreach ($payload['data'] as $item) {
                $seenIds[] = $item['id'];
            }

            $cursor = $payload['meta']['next_cursor'];
        } while (null !== $cursor);

        $this->assertCount(\count(array_unique($seenIds)), $seenIds);
        $this->assertNotEmpty($seenIds);
    }

    public function testListUsersWithInvalidCursorReturns400(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', '/api/v1/users?pagination=cursor&cursor=not-a-valid-cursor!!!');

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(400, $client->getResponse()->getStatusCode());
        $this->assertSame('invalid_filter', $payload['error']['code']);
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
