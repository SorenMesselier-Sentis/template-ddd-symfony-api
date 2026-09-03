<?php

declare(strict_types=1);

namespace App\Tests\Http\ApiClient;

use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Tests\Http\HttpTestCase;

final class ApiClientManagementControllerTest extends HttpTestCase
{
    public function testAdminCanCreateGetListRotateRevokeAndDeleteAClient(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        // Create
        $client->request(
            'POST',
            '/api/v1/api-clients',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'Billing sync worker', 'scopes' => ['documents:write']], JSON_THROW_ON_ERROR),
        );
        $this->assertJsonEnvelope($client->getResponse(), 201);
        $created = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertArrayHasKey('id', $created);
        $this->assertArrayHasKey('secret', $created);
        $id = $created['id'];

        // Get
        $client->request('GET', "/api/v1/api-clients/{$id}");
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('Billing sync worker', $data['name']);
        $this->assertSame('active', $data['status']);
        $this->assertArrayNotHasKey('secret', $data);

        // List
        $client->request('GET', '/api/v1/api-clients');
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $list = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertContains($id, array_column($list, 'id'));

        // Rotate secret
        $client->request('POST', "/api/v1/api-clients/{$id}/rotate-secret");
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $rotated = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertNotSame($created['secret'], $rotated['secret']);

        // Revoke
        $client->request('POST', "/api/v1/api-clients/{$id}/revoke");
        $this->assertSame(204, $client->getResponse()->getStatusCode());
        $client->request('GET', "/api/v1/api-clients/{$id}");
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('revoked', $data['status']);

        // Delete
        $client->request('DELETE', "/api/v1/api-clients/{$id}");
        $this->assertSame(204, $client->getResponse()->getStatusCode());
        $client->request('GET', "/api/v1/api-clients/{$id}");
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testNonAdminCannotCreateAClient(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $client->request(
            'POST',
            '/api/v1/api-clients',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'Should not be created'], JSON_THROW_ON_ERROR),
        );

        $response = $client->getResponse();
        $this->assertSame(403, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('insufficient_privileges', $payload['error']['code']);
    }

    public function testCreatingAClientIsAudited(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request(
            'POST',
            '/api/v1/api-clients',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'Audited worker'], JSON_THROW_ON_ERROR),
        );
        $id = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $connection = static::getContainer()->get('doctrine')->getConnection();
        $row = $connection->fetchAssociative(
            'SELECT * FROM audit_log WHERE action = ? AND target_id = ?',
            ['api_client.created', $id],
        );

        $this->assertNotFalse($row);
    }

    public function testFixtureClientAppearsInTheList(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request('GET', '/api/v1/api-clients');
        $list = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];

        $this->assertContains(FixtureData::API_CLIENT_TEST_ID, array_column($list, 'id'));
    }
}
