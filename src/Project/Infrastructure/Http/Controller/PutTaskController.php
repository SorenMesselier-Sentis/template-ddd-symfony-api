<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Controller;

use App\Project\Application\Command\ReplaceTask\ReplaceTaskCommand;
use App\Project\Infrastructure\Http\Request\PutTaskRequest;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tasks/{id}', methods: ['PUT'])]
#[OA\Put(
    path: '/api/v1/tasks/{id}',
    operationId: 'putTask',
    summary: 'Replace a task',
    description: 'Replaces the title and assignee. Status and attachment are untouched.',
    tags: ['Projects'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['title'],
        properties: [
            new OA\Property(property: 'title', type: 'string', example: 'Design the homepage mockup v2'),
            new OA\Property(property: 'assigneeId', type: 'string', format: 'uuid', nullable: true),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'Task replaced (empty body)')]
#[OA\Response(response: 403, description: 'Not the project owner')]
#[OA\Response(response: 404, description: 'Task not found')]
#[OA\Response(response: 409, description: 'A task with this title already exists in the project')]
final class PutTaskController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id, PutTaskRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new ReplaceTaskCommand(
            id: $id,
            title: $request->title(),
            assigneeId: $request->assigneeId(),
        ));

        return $this->apiResponse->noContent();
    }
}
