<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Filter\FiltersBuilder;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Query\GetUsers\GetUsersCursorQuery;
use App\User\Application\Query\GetUsers\GetUsersQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/users',
    operationId: 'getUsers',
    summary: 'List users',
    description: 'Returns a paginated list. Supports `page`, `limit`, and filters `email`, `firstName`, `lastName`. '
        .'Set `pagination=cursor` to switch to keyset pagination (`cursor`/`limit`, ordered by `createdAt DESC`) '
        .'instead — recommended over `page`/`limit` for large or fast-changing collections.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1))]
#[OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100, minimum: 1))]
#[OA\Parameter(name: 'pagination', in: 'query', description: 'Set to "cursor" to use keyset pagination instead of page/limit.', schema: new OA\Schema(type: 'string', enum: ['offset', 'cursor'], default: 'offset'))]
#[OA\Parameter(name: 'cursor', in: 'query', description: 'Opaque cursor from a previous response\'s `meta.next_cursor` (cursor mode only).', schema: new OA\Schema(type: 'string'))]
#[OA\Parameter(name: 'email', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
#[OA\Parameter(name: 'firstName', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
#[OA\Parameter(name: 'lastName', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
#[OA\Response(
    response: 200,
    description: 'Paginated list of users',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'data',
                type: 'array',
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'first_name', type: 'string'),
                        new OA\Property(property: 'last_name', type: 'string'),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Property(
                property: 'meta',
                properties: [
                    new OA\Property(property: 'page', type: 'integer', example: 1),
                    new OA\Property(property: 'limit', type: 'integer', example: 10),
                    new OA\Property(property: 'total_items', type: 'integer', example: 245),
                    new OA\Property(property: 'total_pages', type: 'integer', example: 25),
                    new OA\Property(property: 'has_next', type: 'boolean', example: true),
                    new OA\Property(property: 'has_previous', type: 'boolean', example: false),
                ],
                type: 'object',
            ),
            new OA\Property(
                property: 'links',
                properties: [
                    new OA\Property(property: 'self', type: 'string', example: '/v1/users?page=1&limit=10'),
                    new OA\Property(property: 'next', type: 'string', nullable: true, example: '/v1/users?page=2&limit=10'),
                    new OA\Property(property: 'previous', type: 'string', nullable: true, example: null),
                ],
                type: 'object',
            ),
        ],
    ),
)]
final class GetUsersController
{
    private const ALLOWED_FILTERS = [
        'email' => 'equal',
        'firstName' => 'equal',
        'lastName' => 'equal',
    ];

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $filters = FiltersBuilder::fromRequest($request, self::ALLOWED_FILTERS);

        if (FiltersBuilder::isCursorMode($request)) {
            $cursorPagination = FiltersBuilder::cursorPaginationFromRequest($request);
            $result = $this->queryBus->ask(new GetUsersCursorQuery($filters, $cursorPagination));

            return $this->apiResponse->paginatedByCursor(
                data: $result->users,
                limit: $result->limit,
                hasMore: $result->hasMore,
                nextCursor: $result->nextCursor,
                request: $request,
            );
        }

        $result = $this->queryBus->ask(new GetUsersQuery($filters));

        return $this->apiResponse->paginated(
            data: $result->users,
            total: $result->total,
            page: $result->page,
            limit: $result->limit,
            request: $request,
        );
    }
}
