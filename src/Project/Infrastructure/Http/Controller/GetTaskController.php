<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Controller;

use App\Project\Application\Query\GetTask\GetTaskQuery;
use App\Project\Application\Query\GetTask\TaskResponse;
use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tasks/{id}', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/tasks/{id}',
    operationId: 'getTask',
    summary: 'Get a task',
    tags: ['Projects'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 200, description: 'Task found')]
#[OA\Response(response: 403, description: 'Not the project owner')]
#[OA\Response(response: 404, description: 'Task not found')]
final class GetTaskController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        /** @var TaskResponse $response */
        $response = $this->queryBus->ask(new GetTaskQuery($id));

        return $this->apiResponse->success($response);
    }
}
