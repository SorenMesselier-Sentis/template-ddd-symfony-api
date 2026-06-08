<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Query\GetDocuments\DocumentsResponse;
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
    description: 'Returns a paginated list of active documents owned by the authenticated user. Supports filters on `bucketName`, `mimeType`, and `createdAt` range.',
    tags: ['Documents'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1))]
#[OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100, minimum: 1))]
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

        $result = $this->queryBus->ask(new GetDocumentsQuery($filters));
        if (!$result instanceof DocumentsResponse) {
            throw new \RuntimeException(sprintf('Expected %s, %s given.', DocumentsResponse::class, get_debug_type($result)));
        }

        return $this->apiResponse->paginated(
            data: $result->documents,
            total: $result->total,
            page: $result->page,
            limit: $result->limit,
            request: $request,
        );
    }
}
