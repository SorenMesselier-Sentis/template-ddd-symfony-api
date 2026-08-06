<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Controller;

use App\Project\Application\Query\GetProjects\GetProjectsQuery;
use App\Project\Application\Query\GetProjects\ProjectsResponse;
use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Filter\FiltersBuilder;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/projects',
    operationId: 'getProjects',
    summary: 'List the authenticated user\'s projects',
    description: 'Returns a paginated list. Supports `page`, `limit`, and filters `name`, `status`.',
    tags: ['Projects'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1))]
#[OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100, minimum: 1))]
#[OA\Parameter(name: 'name', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
#[OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'archived']))]
#[OA\Response(response: 200, description: 'Paginated list of projects')]
final class GetProjectsController
{
    private const ALLOWED_FILTERS = [
        'name' => 'equal',
        'status' => 'equal',
    ];

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $filters = FiltersBuilder::fromRequest($request, self::ALLOWED_FILTERS);

        /** @var ProjectsResponse $result */
        $result = $this->queryBus->ask(new GetProjectsQuery($filters));

        return $this->apiResponse->paginated(
            data: $result->items,
            total: $result->total,
            page: $result->page,
            limit: $result->limit,
            request: $request,
        );
    }
}
