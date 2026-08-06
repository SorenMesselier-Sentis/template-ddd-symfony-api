<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Tests\Http\HttpTestCase;

final class ExportMyDataControllerTest extends HttpTestCase
{
    public function testExportAggregatesProfileAndDocumentsForCurrentUser(): void
    {
        $client = $this->createAuthenticatedClient('user');
        $client->request('GET', '/api/v1/users/me/export');

        $response = $client->getResponse();
        $this->assertJsonEnvelope($response, 200);
        $this->assertSame(
            'attachment; filename="personal-data-export.json"',
            $response->headers->get('Content-Disposition'),
        );

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('exported_at', $payload['data']);
        $this->assertSame(FixtureData::USER_JANE_EMAIL, $payload['data']['profile']['email']);
        $this->assertGreaterThanOrEqual(1, \count($payload['data']['documents']));
    }

    public function testExportRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/users/me/export');

        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}
