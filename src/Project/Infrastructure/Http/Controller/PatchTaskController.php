<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Controller;

use App\Project\Application\Command\UpdateTask\UpdateTaskCommand;
use App\Project\Infrastructure\Http\Request\PatchTaskRequest;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Exception\EmptyPatchException;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tasks/{id}', methods: ['PATCH'])]
#[OA\Patch(
    path: '/api/v1/tasks/{id}',
    operationId: 'patchTask',
    summary: 'Partially update a task',
    description: 'Updates only the fields sent in the body. At least one field is required. `status` accepts '
        .'"todo", "in_progress" or "done" only — deletion goes through DELETE. `assigneeId` reassigns; there is no '
        .'way to unassign via PATCH (see Task::update()).',
    tags: ['Projects'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'title', type: 'string', example: 'Design the homepage mockup v2'),
            new OA\Property(property: 'status', type: 'string', enum: ['todo', 'in_progress', 'done'], example: 'in_progress'),
            new OA\Property(property: 'assigneeId', type: 'string', format: 'uuid'),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'Task updated (empty body)')]
#[OA\Response(response: 403, description: 'Not the project owner')]
#[OA\Response(response: 404, description: 'Task not found')]
#[OA\Response(response: 409, description: 'A task with this title already exists in the project')]
final class PatchTaskController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id, PatchTaskRequest $request): JsonResponse
    {
        if ($request->isEmpty()) {
            throw new EmptyPatchException('At least one field must be provided.');
        }

        $this->commandBus->dispatch(new UpdateTaskCommand(
            id: $id,
            title: $request->title(),
            status: $request->status(),
            assigneeId: $request->assigneeId(),
        ));

        return $this->apiResponse->noContent();
    }
}
