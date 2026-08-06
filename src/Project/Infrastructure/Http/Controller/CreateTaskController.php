<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Controller;

use App\Project\Application\Command\CreateTask\CreateTaskCommand;
use App\Project\Infrastructure\Http\Request\CreateTaskRequest;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/projects/{projectId}/tasks', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/projects/{projectId}/tasks',
    operationId: 'postTasks',
    summary: 'Create a task under a project',
    description: 'Fails with 409 if the project is archived or deleted.',
    tags: ['Projects'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['title'],
        properties: [
            new OA\Property(property: 'title', type: 'string', example: 'Design the homepage mockup'),
            new OA\Property(property: 'assigneeId', type: 'string', format: 'uuid', nullable: true, description: 'References a User id — not validated cross-BC.'),
            new OA\Property(property: 'attachmentId', type: 'string', format: 'uuid', nullable: true, description: 'References a Document id — not validated cross-BC.'),
        ],
    ),
)]
#[OA\Response(
    response: 201,
    description: 'Task created',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'data',
                properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')],
                type: 'object',
            ),
        ],
    ),
)]
#[OA\Response(response: 403, description: 'Not the project owner')]
#[OA\Response(response: 404, description: 'Project not found')]
#[OA\Response(response: 409, description: 'Project is not active, or a task with this title already exists in the project')]
final class CreateTaskController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $projectId, CreateTaskRequest $request): JsonResponse
    {
        $id = Uuid::v4()->toRfc4122();

        $this->commandBus->dispatch(new CreateTaskCommand(
            id: $id,
            projectId: $projectId,
            title: $request->title(),
            assigneeId: $request->assigneeId(),
            attachmentId: $request->attachmentId(),
        ));

        return $this->apiResponse->created(['id' => $id]);
    }
}
