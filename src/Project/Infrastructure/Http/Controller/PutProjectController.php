<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Controller;

use App\Project\Application\Command\ReplaceProject\ReplaceProjectCommand;
use App\Project\Infrastructure\Http\Request\PutProjectRequest;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects/{id}', methods: ['PUT'])]
#[OA\Put(
    path: '/api/v1/projects/{id}',
    operationId: 'putProject',
    summary: 'Replace a project',
    description: 'Replaces the project name. Status is untouched — use PATCH to archive/reactivate, DELETE to remove.',
    tags: ['Projects'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['name'],
        properties: [new OA\Property(property: 'name', type: 'string', example: 'Website redesign v2')],
    ),
)]
#[OA\Response(response: 204, description: 'Project replaced (empty body)')]
#[OA\Response(response: 403, description: 'Not the project owner')]
#[OA\Response(response: 404, description: 'Project not found')]
#[OA\Response(response: 409, description: 'A project with this name already exists for this owner')]
final class PutProjectController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id, PutProjectRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new ReplaceProjectCommand(
            id: $id,
            name: $request->name(),
        ));

        return $this->apiResponse->noContent();
    }
}
