<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class SendTestRealtimeNotificationControllerTest extends HttpTestCase
{
    public function testItPublishesADefaultNotificationWithNoBody(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $client->request('POST', '/api/v1/users/me/realtime-test');

        $this->assertSame(204, $client->getResponse()->getStatusCode());
    }

    public function testItPublishesACustomMessage(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $client->request(
            'POST',
            '/api/v1/users/me/realtime-test',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['message' => 'Hi there'], JSON_THROW_ON_ERROR),
        );

        $this->assertSame(204, $client->getResponse()->getStatusCode());
    }

    public function testItRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/users/me/realtime-test');

        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}
