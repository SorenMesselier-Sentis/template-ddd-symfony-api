<?php

declare(strict_types=1);

namespace App\Tests\Http\Project;

use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Tests\Http\HttpTestCase;

final class TaskHttpTest extends HttpTestCase
{
    public function testOwnerCanCreateListGetUpdateAndDeleteATask(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $projectId = FixtureData::PROJECT_JOHN_WEBSITE_ID;

        $client->request(
            'POST',
            "/api/v1/projects/{$projectId}/tasks",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'title' => 'Write the launch announcement',
                'assigneeId' => FixtureData::USER_JANE_ID,
                'attachmentId' => FixtureData::DOCUMENT_JOHN_INVOICE_ID,
            ], JSON_THROW_ON_ERROR),
        );
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $id = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $client->request('GET', "/api/v1/projects/{$projectId}/tasks");
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $titles = array_column($payload['data'], 'title');
        $this->assertContains('Write the launch announcement', $titles);
        $this->assertContains(FixtureData::TASK_JOHN_WEBSITE_DESIGN_TITLE, $titles);

        $client->request('GET', "/api/v1/tasks/{$id}");
        $this->assertJsonEnvelope($client->getResponse(), 200);
        $task = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('Write the launch announcement', $task['title']);
        $this->assertSame('todo', $task['status']);
        $this->assertSame(FixtureData::USER_JANE_ID, $task['assigneeId']);
        $this->assertSame(FixtureData::DOCUMENT_JOHN_INVOICE_ID, $task['attachmentId']);

        $client->request(
            'PATCH',
            "/api/v1/tasks/{$id}",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['status' => 'in_progress'], JSON_THROW_ON_ERROR),
        );
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $client->request('GET', "/api/v1/tasks/{$id}");
        $task = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('in_progress', $task['status']);

        $client->request('DELETE', "/api/v1/tasks/{$id}");
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $client->request('GET', "/api/v1/tasks/{$id}");
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testCreatingATaskOnAnArchivedProjectFailsWith409(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $projectId = FixtureData::PROJECT_JOHN_WEBSITE_ID;

        $client->request(
            'PATCH',
            "/api/v1/projects/{$projectId}",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['status' => 'archived'], JSON_THROW_ON_ERROR),
        );
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $client->request(
            'POST',
            "/api/v1/projects/{$projectId}/tasks",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['title' => 'Should not be created'], JSON_THROW_ON_ERROR),
        );

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(409, $client->getResponse()->getStatusCode());
        $this->assertSame('project.not_active', $payload['error']['code']);
    }

    public function testCreatingATaskWithADuplicateTitleInTheSameProjectFailsWith409(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $projectId = FixtureData::PROJECT_JOHN_WEBSITE_ID;

        $client->request(
            'POST',
            "/api/v1/projects/{$projectId}/tasks",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['title' => FixtureData::TASK_JOHN_WEBSITE_DESIGN_TITLE], JSON_THROW_ON_ERROR),
        );

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(409, $client->getResponse()->getStatusCode());
        $this->assertSame('task.already_exists', $payload['error']['code']);
    }

    public function testNonOwnerCannotAccessTasksOfAnotherUsersProject(): void
    {
        $client = $this->createAuthenticatedClient('user');
        $projectId = FixtureData::PROJECT_JOHN_WEBSITE_ID;
        $taskId = FixtureData::TASK_JOHN_WEBSITE_DESIGN_ID;

        $client->request('GET', "/api/v1/projects/{$projectId}/tasks");
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request(
            'POST',
            "/api/v1/projects/{$projectId}/tasks",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['title' => 'Should not be created'], JSON_THROW_ON_ERROR),
        );
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('GET', "/api/v1/tasks/{$taskId}");
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('DELETE', "/api/v1/tasks/{$taskId}");
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }
}
