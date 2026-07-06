<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Command\DeleteBucket\DeleteBucketCommand;
use App\Document\Application\Command\DeleteBucket\DeleteBucketResult;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/buckets/{name}', requirements: ['name' => '[a-z0-9-]+'], methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/v1/buckets/{name}',
    operationId: 'deleteBucket',
    summary: 'Delete an object storage bucket',
    description: 'Deletes an empty bucket from S3-compatible object storage. Requires `ROLE_ADMIN`.',
    tags: ['Buckets'],
    security: [['bearer' => []]],
)]
#[OA\Response(response: 200, description: 'Bucket deleted')]
#[OA\Response(response: 403, description: 'Forbidden')]
#[OA\Response(response: 404, description: 'Bucket not found')]
#[OA\Response(response: 409, description: 'Bucket is not empty')]
final class DeleteBucketController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $name): JsonResponse
    {
        /** @var DeleteBucketResult $result */
        $result = $this->commandBus->dispatch(new DeleteBucketCommand(name: $name));

        return $this->apiResponse->success(['name' => $result->name]);
    }
}
