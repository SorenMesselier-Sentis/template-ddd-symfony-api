<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Controller;

use App\Project\Application\Query\GetTasks\GetTasksQuery;
use App\Project\Application\Query\GetTasks\TasksResponse;
use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Filter\FiltersBuilder;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects/{projectId}/tasks', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/projects/{projectId}/tasks',
    operationId: 'getTasks',
    summary: 'List a project\'s tasks',
    description: 'Returns a paginated list. Supports `page`, `limit`, and filters `status`, `assigneeId`.',
    tags: ['Projects'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1))]
#[OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100, minimum: 1))]
#[OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['todo', 'in_progress', 'done']))]
#[OA\Parameter(name: 'assigneeId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 200, description: 'Paginated list of tasks')]
#[OA\Response(response: 403, description: 'Not the project owner')]
#[OA\Response(response: 404, description: 'Project not found')]
final class GetTasksController
{
    private const ALLOWED_FILTERS = [
        'status' => 'equal',
        'assigneeId' => 'equal',
    ];

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $projectId, Request $request): JsonResponse
    {
        $filters = FiltersBuilder::fromRequest($request, self::ALLOWED_FILTERS);

        /** @var TasksResponse $result */
        $result = $this->queryBus->ask(new GetTasksQuery($projectId, $filters));

        return $this->apiResponse->paginated(
            data: $result->items,
            total: $result->total,
            page: $result->page,
            limit: $result->limit,
            request: $request,
        );
    }
}
