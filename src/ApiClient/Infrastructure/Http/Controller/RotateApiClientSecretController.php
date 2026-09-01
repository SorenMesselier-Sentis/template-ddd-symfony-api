<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Http\Controller;

use App\ApiClient\Application\Command\RotateApiClientSecret\RotateApiClientSecretCommand;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api-clients/{id}/rotate-secret', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/api-clients/{id}/rotate-secret',
    operationId: 'postApiClientRotateSecret',
    summary: 'Rotate an OAuth2 machine client\'s secret',
    description: 'Requires admin (`ROLE_ADMIN`). Invalidates every access token already issued to this client. The new plain-text `secret` is returned only in this response.',
    tags: ['ApiClients'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'Secret rotated',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'data',
                properties: [new OA\Property(property: 'secret', type: 'string')],
                type: 'object',
            ),
        ],
    ),
)]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
#[OA\Response(response: 404, description: 'API client not found')]
final class RotateApiClientSecretController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        /** @var array{secret: string} $result */
        $result = $this->commandBus->dispatch(new RotateApiClientSecretCommand($id));

        return $this->apiResponse->success($result);
    }
}
