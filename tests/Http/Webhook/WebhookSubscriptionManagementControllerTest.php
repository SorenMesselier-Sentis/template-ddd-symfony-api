<?php

declare(strict_types=1);

namespace App\Tests\Http\Webhook;

use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Tests\Http\HttpTestCase;

final class WebhookSubscriptionManagementControllerTest extends HttpTestCase
{
    public function testAdminCanCreateGetListUpdateRotateSecretDisableEnableAndDeleteASubscription(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        // Create
        $client->request(
            'POST',
            '/api/v1/webhook-subscriptions',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Billing system sync',
                'url' => 'https://example.com/webhooks/inbound',
                'event_names' => ['document.uploaded', 'user.created'],
            ], \JSON_THROW_ON_ERROR),
        );
        $this->assertJsonEnvelope($client->getResponse(), 201);
        $created = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['data'];
        $this->assertArrayHasKey('id', $created);
        $this->assertArrayHasKey('secret', $created);
        $id = $created['id'];

        // Get
        $client->request('GET', "/api/v1/webhook-subscriptions/{$id}");
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('Billing system sync', $data['name']);
        $this->assertSame('active', $data['status']);
        $this->assertArrayNotHasKey('secret', $data);

        // List
        $client->request('GET', '/api/v1/webhook-subscriptions');
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $list = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['data'];
        $this->assertContains($id, array_column($list, 'id'));

        // Update
        $client->request(
            'PUT',
            "/api/v1/webhook-subscriptions/{$id}",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Renamed sync',
                'url' => 'https://renamed.example.com/inbound',
                'event_names' => ['task.created'],
            ], \JSON_THROW_ON_ERROR),
        );
        $this->assertSame(204, $client->getResponse()->getStatusCode());
        $client->request('GET', "/api/v1/webhook-subscriptions/{$id}");
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('Renamed sync', $data['name']);
        $this->assertSame('https://renamed.example.com/inbound', $data['url']);
        $this->assertSame(['task.created'], $data['event_names']);

        // Rotate secret
        $client->request('POST', "/api/v1/webhook-subscriptions/{$id}/rotate-secret");
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $rotated = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['data'];
        $this->assertNotSame($created['secret'], $rotated['secret']);

        // Disable
        $client->request('POST', "/api/v1/webhook-subscriptions/{$id}/disable");
        $this->assertSame(204, $client->getResponse()->getStatusCode());
        $client->request('GET', "/api/v1/webhook-subscriptions/{$id}");
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('disabled', $data['status']);

        // Enable
        $client->request('POST', "/api/v1/webhook-subscriptions/{$id}/enable");
        $this->assertSame(204, $client->getResponse()->getStatusCode());
        $client->request('GET', "/api/v1/webhook-subscriptions/{$id}");
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('active', $data['status']);

        // Delete
        $client->request('DELETE', "/api/v1/webhook-subscriptions/{$id}");
        $this->assertSame(204, $client->getResponse()->getStatusCode());
        $client->request('GET', "/api/v1/webhook-subscriptions/{$id}");
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testNonAdminCannotCreateASubscription(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $client->request(
            'POST',
            '/api/v1/webhook-subscriptions',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Should not be created',
                'url' => 'https://example.com/inbound',
                'event_names' => ['document.uploaded'],
            ], \JSON_THROW_ON_ERROR),
        );

        $response = $client->getResponse();
        $this->assertSame(403, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertSame('insufficient_privileges', $payload['error']['code']);
    }

    public function testCreatingASubscriptionIsAudited(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request(
            'POST',
            '/api/v1/webhook-subscriptions',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Audited subscription',
                'url' => 'https://example.com/inbound',
                'event_names' => ['document.uploaded'],
            ], \JSON_THROW_ON_ERROR),
        );
        $id = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['data']['id'];

        $connection = static::getContainer()->get('doctrine')->getConnection();
        $row = $connection->fetchAssociative(
            'SELECT * FROM audit_log WHERE action = ? AND target_id = ?',
            ['webhook_subscription.created', $id],
        );

        $this->assertNotFalse($row);
    }

    public function testCreatingASubscriptionWithAPlainHttpUrlIsRejected(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request(
            'POST',
            '/api/v1/webhook-subscriptions',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Insecure endpoint',
                'url' => 'http://example.com/inbound',
                'event_names' => ['document.uploaded'],
            ], \JSON_THROW_ON_ERROR),
        );

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertSame('webhook.invalid_url', $payload['error']['code']);
    }

    public function testCreatingASubscriptionTargetingAPrivateIpIsRejected(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request(
            'POST',
            '/api/v1/webhook-subscriptions',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'SSRF attempt',
                'url' => 'https://169.254.169.254/latest/meta-data',
                'event_names' => ['document.uploaded'],
            ], \JSON_THROW_ON_ERROR),
        );

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertSame('webhook.invalid_url', $payload['error']['code']);
    }

    public function testFixtureSubscriptionAppearsInTheList(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request('GET', '/api/v1/webhook-subscriptions');
        $list = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['data'];

        $this->assertContains(FixtureData::WEBHOOK_SUBSCRIPTION_TEST_ID, array_column($list, 'id'));
    }
}
