<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Controller;

use App\Project\Application\Command\UpdateProject\UpdateProjectCommand;
use App\Project\Infrastructure\Http\Request\PatchProjectRequest;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Exception\EmptyPatchException;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects/{id}', methods: ['PATCH'])]
#[OA\Patch(
    path: '/api/v1/projects/{id}',
    operationId: 'patchProject',
    summary: 'Partially update a project',
    description: 'Updates only the fields sent in the body. At least one field is required. `status` accepts '
        .'"active" or "archived" only — deletion goes through DELETE.',
    tags: ['Projects'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Website redesign v2'),
            new OA\Property(property: 'status', type: 'string', enum: ['active', 'archived'], example: 'archived'),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'Project updated (empty body)')]
#[OA\Response(response: 403, description: 'Not the project owner')]
#[OA\Response(response: 404, description: 'Project not found')]
#[OA\Response(response: 409, description: 'A project with this name already exists for this owner')]
final class PatchProjectController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id, PatchProjectRequest $request): JsonResponse
    {
        if ($request->isEmpty()) {
            throw new EmptyPatchException('At least one field must be provided.');
        }

        $this->commandBus->dispatch(new UpdateProjectCommand(
            id: $id,
            name: $request->name(),
            status: $request->status(),
        ));

        return $this->apiResponse->noContent();
    }
}
