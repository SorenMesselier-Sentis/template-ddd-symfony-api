<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Controller;

use App\Project\Application\Command\CreateProject\CreateProjectCommand;
use App\Project\Infrastructure\Http\Request\CreateProjectRequest;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/projects', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/projects',
    operationId: 'postProjects',
    summary: 'Create a project',
    description: 'Creates a project owned by the authenticated user.',
    tags: ['Projects'],
    security: [['bearer' => []]],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['name'],
        properties: [new OA\Property(property: 'name', type: 'string', example: 'Website redesign')],
    ),
)]
#[OA\Response(
    response: 201,
    description: 'Project created',
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
#[OA\Response(response: 409, description: 'A project with this name already exists for this owner')]
final class CreateProjectController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(CreateProjectRequest $request): JsonResponse
    {
        $id = Uuid::v4()->toRfc4122();

        $this->commandBus->dispatch(new CreateProjectCommand(
            id: $id,
            name: $request->name(),
        ));

        return $this->apiResponse->created(['id' => $id]);
    }
}
