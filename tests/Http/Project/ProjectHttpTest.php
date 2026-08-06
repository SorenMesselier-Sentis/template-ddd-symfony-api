<?php

declare(strict_types=1);

namespace App\Tests\Http\Project;

use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Tests\Http\HttpTestCase;

final class ProjectHttpTest extends HttpTestCase
{
    public function testOwnerCanCreateListGetAndReplaceAProject(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request(
            'POST',
            '/api/v1/projects',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'New client project'], JSON_THROW_ON_ERROR),
        );
        $created = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $id = $created['data']['id'];

        $client->request('GET', '/api/v1/projects');
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $names = array_column($payload['data'], 'name');
        $this->assertContains('New client project', $names);
        $this->assertContains(FixtureData::PROJECT_JOHN_WEBSITE_NAME, $names);

        $client->request('GET', "/api/v1/projects/{$id}");
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $project = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('New client project', $project['name']);
        $this->assertSame('active', $project['status']);

        $client->request(
            'PUT',
            "/api/v1/projects/{$id}",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'Renamed client project'], JSON_THROW_ON_ERROR),
        );
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $client->request('GET', "/api/v1/projects/{$id}");
        $project = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('Renamed client project', $project['name']);
    }

    public function testPatchCanArchiveAndReactivateAProject(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request(
            'PATCH',
            '/api/v1/projects/'.FixtureData::PROJECT_JOHN_WEBSITE_ID,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['status' => 'archived'], JSON_THROW_ON_ERROR),
        );
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/v1/projects/'.FixtureData::PROJECT_JOHN_WEBSITE_ID);
        $project = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('archived', $project['status']);

        $client->request(
            'PATCH',
            '/api/v1/projects/'.FixtureData::PROJECT_JOHN_WEBSITE_ID,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['status' => 'active'], JSON_THROW_ON_ERROR),
        );
        $this->assertSame(204, $client->getResponse()->getStatusCode());
    }

    public function testPatchRejectsDeletedAsAStatusValue(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request(
            'PATCH',
            '/api/v1/projects/'.FixtureData::PROJECT_JOHN_WEBSITE_ID,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['status' => 'deleted'], JSON_THROW_ON_ERROR),
        );

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(400, $client->getResponse()->getStatusCode());
        $this->assertSame('project.invalid_status', $payload['error']['code']);
    }

    public function testCreatingAProjectWithADuplicateNameFailsWith409(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request(
            'POST',
            '/api/v1/projects',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => FixtureData::PROJECT_JOHN_WEBSITE_NAME], JSON_THROW_ON_ERROR),
        );

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(409, $client->getResponse()->getStatusCode());
        $this->assertSame('project.already_exists', $payload['error']['code']);
    }

    public function testNonOwnerCannotAccessAnotherUsersProject(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $client->request('GET', '/api/v1/projects/'.FixtureData::PROJECT_JOHN_WEBSITE_ID);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $this->assertSame('forbidden', $payload['error']['code']);

        $client->request(
            'PATCH',
            '/api/v1/projects/'.FixtureData::PROJECT_JOHN_WEBSITE_ID,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'Hijacked'], JSON_THROW_ON_ERROR),
        );
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('DELETE', '/api/v1/projects/'.FixtureData::PROJECT_JOHN_WEBSITE_ID);
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testDeletingAProjectWithActiveTasksFailsWith409(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        // The seeded project has one active (todo) task — see TaskFixture.
        $client->request('DELETE', '/api/v1/projects/'.FixtureData::PROJECT_JOHN_WEBSITE_ID);

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(409, $client->getResponse()->getStatusCode());
        $this->assertSame('project.has_active_tasks', $payload['error']['code']);
    }

    public function testDeletingAProjectWithNoTasksSucceeds(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request(
            'POST',
            '/api/v1/projects',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'Empty project'], JSON_THROW_ON_ERROR),
        );
        $id = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $client->request('DELETE', "/api/v1/projects/{$id}");
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $client->request('GET', "/api/v1/projects/{$id}");
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
