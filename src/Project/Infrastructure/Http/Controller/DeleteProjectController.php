<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Controller;

use App\Project\Application\Command\DeleteProject\DeleteProjectCommand;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects/{id}', methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/v1/projects/{id}',
    operationId: 'deleteProject',
    summary: 'Delete a project',
    description: 'Soft-deletes the project. Fails while it still has non-deleted tasks.',
    tags: ['Projects'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 204, description: 'Project deleted')]
#[OA\Response(response: 403, description: 'Not the project owner')]
#[OA\Response(response: 404, description: 'Project not found')]
#[OA\Response(response: 409, description: 'Project still has active tasks')]
final class DeleteProjectController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteProjectCommand($id));

        return $this->apiResponse->noContent();
    }
}
