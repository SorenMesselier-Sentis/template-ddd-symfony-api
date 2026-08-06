<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Controller;

use App\Project\Application\Query\GetProject\GetProjectQuery;
use App\Project\Application\Query\GetProject\ProjectResponse;
use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects/{id}', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/projects/{id}',
    operationId: 'getProject',
    summary: 'Get a project',
    tags: ['Projects'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 200, description: 'Project found')]
#[OA\Response(response: 403, description: 'Not the project owner')]
#[OA\Response(response: 404, description: 'Project not found')]
final class GetProjectController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        /** @var ProjectResponse $response */
        $response = $this->queryBus->ask(new GetProjectQuery($id));

        return $this->apiResponse->success($response);
    }
}
