<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Http\Controller;

use App\ApiClient\Application\Command\RevokeApiClient\RevokeApiClientCommand;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api-clients/{id}/revoke', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/api-clients/{id}/revoke',
    operationId: 'postApiClientRevoke',
    summary: 'Revoke an OAuth2 machine client',
    description: 'Requires admin (`ROLE_ADMIN`). Blocks new token issuance and invalidates every access token already issued to this client.',
    tags: ['ApiClients'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 204, description: 'API client revoked')]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
#[OA\Response(response: 404, description: 'API client not found')]
final class RevokeApiClientController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new RevokeApiClientCommand($id));

        return $this->apiResponse->noContent();
    }
}
