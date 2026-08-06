<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class GetFeatureFlagsControllerTest extends HttpTestCase
{
    public function testAdminListsFeatureFlagsIncludingTheSeededOne(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', '/api/v1/feature-flags');

        $response = $client->getResponse();
        $this->assertJsonEnvelope($response, 200);

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $keys = array_column($payload['data'], 'key');

        $this->assertContains('cursor_pagination', $keys);
    }

    public function testNonAdminCannotListFeatureFlags(): void
    {
        $client = $this->createAuthenticatedClient('user');
        $client->request('GET', '/api/v1/feature-flags');

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $this->assertSame('insufficient_privileges', $payload['error']['code']);
    }
}
