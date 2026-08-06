<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class PutFeatureFlagControllerTest extends HttpTestCase
{
    public function testAdminCanCreateAndUpdateAFlag(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $key = 'test_flag_http';

        $client->request(
            'PUT',
            "/api/v1/feature-flags/{$key}",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['enabled' => true, 'description' => 'demo'], JSON_THROW_ON_ERROR),
        );
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/v1/feature-flags');
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $flag = current(array_filter($payload['data'], static fn ($f) => $key === $f['key']));

        $this->assertNotFalse($flag);
        $this->assertTrue($flag['enabled']);
        $this->assertSame('demo', $flag['description']);
    }

    public function testNonAdminCannotPutAFlag(): void
    {
        $client = $this->createAuthenticatedClient('user');
        $client->request(
            'PUT',
            '/api/v1/feature-flags/cursor_pagination',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['enabled' => false], JSON_THROW_ON_ERROR),
        );

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $this->assertSame('insufficient_privileges', $payload['error']['code']);
    }

    public function testDisablingCursorPaginationBlocksTheCursorEndpointWith403(): void
    {
        $admin = $this->createAuthenticatedClient('admin');
        $admin->request(
            'PUT',
            '/api/v1/feature-flags/cursor_pagination',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['enabled' => false, 'description' => 'disabled for test'], JSON_THROW_ON_ERROR),
        );
        $this->assertSame(204, $admin->getResponse()->getStatusCode());

        $admin->request('GET', '/api/v1/users?pagination=cursor');
        $payload = json_decode((string) $admin->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(403, $admin->getResponse()->getStatusCode());
        $this->assertSame('feature_flag.disabled', $payload['error']['code']);
    }
}
