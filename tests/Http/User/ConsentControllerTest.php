<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class ConsentControllerTest extends HttpTestCase
{
    public function testItRecordsListsAndWithdrawsConsent(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $client->request(
            'POST',
            '/api/v1/users/me/consents',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['type' => 'marketing_emails', 'version' => '2026-09-16'], JSON_THROW_ON_ERROR),
        );
        $this->assertJsonEnvelope($client->getResponse(), 201);

        $client->request('GET', '/api/v1/users/me/consents');
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $marketing = array_values(array_filter(
            $payload['data']['consents'],
            static fn (array $consent): bool => 'marketing_emails' === $consent['type'],
        ));
        $this->assertCount(1, $marketing);
        $this->assertTrue($marketing[0]['active']);

        $client->request('DELETE', '/api/v1/users/me/consents/marketing_emails');
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/v1/users/me/consents');
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $marketing = array_values(array_filter(
            $payload['data']['consents'],
            static fn (array $consent): bool => 'marketing_emails' === $consent['type'],
        ));
        $this->assertFalse($marketing[0]['active']);
    }

    public function testWithdrawingAConsentThatWasNeverGivenIsIdempotent(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $client->request('DELETE', '/api/v1/users/me/consents/marketing_emails');

        $this->assertSame(204, $client->getResponse()->getStatusCode());
    }

    public function testConsentEndpointsRequireAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/users/me/consents');

        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}
