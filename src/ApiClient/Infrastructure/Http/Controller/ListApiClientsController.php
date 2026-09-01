<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Http\Controller;

use App\ApiClient\Application\Query\GetApiClientsCollection\ApiClientsResponse;
use App\ApiClient\Application\Query\GetApiClientsCollection\GetApiClientsCollectionQuery;
use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Filter\FiltersBuilder;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api-clients', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/api-clients',
    operationId: 'getApiClients',
    summary: 'List OAuth2 machine clients',
    description: 'Requires admin (`ROLE_ADMIN`). Returns a paginated list. Supports `page`, `limit`, and filter `status`.',
    tags: ['ApiClients'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1))]
#[OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100, minimum: 1))]
#[OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'revoked']))]
#[OA\Response(response: 200, description: 'Paginated list of API clients')]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
final class ListApiClientsController
{
    private const ALLOWED_FILTERS = [
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

        /** @var ApiClientsResponse $result */
        $result = $this->queryBus->ask(new GetApiClientsCollectionQuery($filters));

        return $this->apiResponse->paginated(
            data: $result->items,
            total: $result->total,
            page: $result->page,
            limit: $result->limit,
            request: $request,
        );
    }
}
