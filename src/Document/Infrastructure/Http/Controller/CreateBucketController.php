<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Controller;

use App\Document\Application\Command\CreateBucket\CreateBucketCommand;
use App\Document\Application\Command\CreateBucket\CreateBucketResult;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/buckets', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/buckets',
    operationId: 'postBuckets',
    summary: 'Create a MinIO bucket',
    description: 'Creates a new bucket in MinIO. Requires `ROLE_ADMIN`.',
    tags: ['Buckets'],
    security: [['bearer' => []]],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['name'],
        properties: [
            new OA\Property(property: 'name', type: 'string', example: 'documents'),
        ],
    ),
)]
#[OA\Response(response: 201, description: 'Bucket created')]
#[OA\Response(response: 400, description: 'Invalid bucket name')]
#[OA\Response(response: 403, description: 'Forbidden')]
#[OA\Response(response: 409, description: 'Bucket already exists')]
final class CreateBucketController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($request->getContent(), true);
        $name = is_array($payload) ? ($payload['name'] ?? '') : '';

        if (!is_string($name) || '' === trim($name)) {
            throw new BadRequestHttpException('A bucket name is required.');
        }

        /** @var CreateBucketResult $result */
        $result = $this->commandBus->dispatch(new CreateBucketCommand(name: $name));

        return $this->apiResponse->created([
            'name' => $result->name,
            'createdAt' => $result->createdAt,
        ]);
    }
}
