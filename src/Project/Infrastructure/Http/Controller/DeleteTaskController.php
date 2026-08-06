<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Controller;

use App\Project\Application\Command\DeleteTask\DeleteTaskCommand;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tasks/{id}', methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/v1/tasks/{id}',
    operationId: 'deleteTask',
    summary: 'Delete a task',
    description: 'Soft-deletes the task.',
    tags: ['Projects'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 204, description: 'Task deleted')]
#[OA\Response(response: 403, description: 'Not the project owner')]
#[OA\Response(response: 404, description: 'Task not found')]
final class DeleteTaskController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteTaskCommand($id));

        return $this->apiResponse->noContent();
    }
}
