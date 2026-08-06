<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Query\GetDocuments\GetDocumentsCursorQuery;
use App\Document\Application\Query\GetDocuments\GetDocumentsQuery;
use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Filter\FiltersBuilder;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/documents', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/documents',
    operationId: 'getDocuments',
    summary: 'List documents for the authenticated user',
    description: 'Returns a paginated list of active documents owned by the authenticated user. Supports filters on `bucketName`, `mimeType`, and `createdAt` range. '
        .'Set `pagination=cursor` to switch to keyset pagination (`cursor`/`limit`, ordered by `createdAt DESC`) instead.',
    tags: ['Documents'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1))]
#[OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100, minimum: 1))]
#[OA\Parameter(name: 'pagination', in: 'query', description: 'Set to "cursor" to use keyset pagination instead of page/limit.', schema: new OA\Schema(type: 'string', enum: ['offset', 'cursor'], default: 'offset'))]
#[OA\Parameter(name: 'cursor', in: 'query', description: 'Opaque cursor from a previous response\'s `meta.next_cursor` (cursor mode only).', schema: new OA\Schema(type: 'string'))]
#[OA\Parameter(name: 'bucketName', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'documents'))]
#[OA\Parameter(name: 'mimeType', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'application/pdf'))]
#[OA\Parameter(
    name: 'createdAt',
    in: 'query',
    required: false,
    description: 'Date range filter. Example: createdAt[min]=2026-01-01&createdAt[max]=2026-12-31',
    schema: new OA\Schema(type: 'object'),
)]
#[OA\Response(response: 200, description: 'Paginated list of documents')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class GetDocumentsController
{
    private const ALLOWED_FILTERS = [
        'bucketName' => 'equal',
        'mimeType' => 'equal',
        'createdAt' => 'range',
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
            $result = $this->queryBus->ask(new GetDocumentsCursorQuery($filters, $cursorPagination));

            return $this->apiResponse->paginatedByCursor(
                data: $result->documents,
                limit: $result->limit,
                hasMore: $result->hasMore,
                nextCursor: $result->nextCursor,
                request: $request,
            );
        }

        $result = $this->queryBus->ask(new GetDocumentsQuery($filters));

        return $this->apiResponse->paginated(
            data: $result->documents,
            total: $result->total,
            page: $result->page,
            limit: $result->limit,
            request: $request,
        );
    }
}
