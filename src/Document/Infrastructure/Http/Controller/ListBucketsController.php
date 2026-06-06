<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Query\ListBuckets\ListBucketsQuery;
use App\Document\Application\Query\ListBuckets\ListBucketsResult;
use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/buckets', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/buckets',
    operationId: 'getBuckets',
    summary: 'List MinIO buckets',
    description: 'Returns all buckets with their creation date. Requires `ROLE_ADMIN`.',
    tags: ['Buckets'],
    security: [['bearer' => []]],
)]
#[OA\Response(response: 200, description: 'Bucket list')]
#[OA\Response(response: 403, description: 'Forbidden')]
final class ListBucketsController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        /** @var ListBucketsResult $result */
        $result = $this->queryBus->ask(new ListBucketsQuery());

        return $this->apiResponse->success(['buckets' => $result->buckets]);
    }
}
