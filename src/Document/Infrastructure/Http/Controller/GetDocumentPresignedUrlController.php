<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Query\GetDocumentPresignedUrl\GetDocumentPresignedUrlQuery;
use App\Document\Application\Query\GetDocumentPresignedUrl\GetDocumentPresignedUrlResult;
use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/documents/{id}/presigned-url', requirements: ['id' => '[0-9a-f\-]+'], methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/documents/{id}/presigned-url',
    operationId: 'getDocumentPresignedUrl',
    summary: 'Get a presigned download URL for a document',
    tags: ['Documents'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(
    name: 'ttl',
    in: 'query',
    required: false,
    description: 'URL validity in seconds (60–604800). Defaults to MINIO_PRESIGNED_URL_TTL.',
    schema: new OA\Schema(type: 'integer', minimum: 60, maximum: 604800),
)]
#[OA\Response(response: 200, description: 'Presigned URL generated')]
#[OA\Response(response: 403, description: 'Forbidden')]
#[OA\Response(response: 404, description: 'Document not found')]
final class GetDocumentPresignedUrlController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $ttl = $request->query->has('ttl')
            ? $request->query->getInt('ttl')
            : null;

        /** @var GetDocumentPresignedUrlResult $result */
        $result = $this->queryBus->ask(new GetDocumentPresignedUrlQuery(
            documentId: $id,
            ttlSeconds: $ttl,
        ));

        return $this->apiResponse->success([
            'documentId' => $result->documentId,
            'presignedUrl' => $result->presignedUrl,
            'expiresIn' => $result->expiresIn,
            'expiresAt' => $result->expiresAt,
        ]);
    }
}
