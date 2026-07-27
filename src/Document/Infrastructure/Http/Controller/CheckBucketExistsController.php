<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Query\CheckBucketExists\CheckBucketExistsQuery;
use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/buckets/{name}/exists', requirements: ['name' => '[a-z0-9-]+'], methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/buckets/{name}/exists',
    operationId: 'getBucketExists',
    summary: 'Check if a bucket exists',
    description: 'Returns whether the bucket exists in object storage. Requires `ROLE_ADMIN`.',
    tags: ['Buckets'],
    security: [['bearer' => []]],
)]
#[OA\Response(response: 200, description: 'Existence check result')]
#[OA\Response(response: 400, description: 'Invalid bucket name')]
#[OA\Response(response: 403, description: 'Forbidden')]
final class CheckBucketExistsController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $name): JsonResponse
    {
        $result = $this->queryBus->ask(new CheckBucketExistsQuery(name: $name));

        return $this->apiResponse->success([
            'name' => $result->name,
            'exists' => $result->exists,
        ]);
    }
}
