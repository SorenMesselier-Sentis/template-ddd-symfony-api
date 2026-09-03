<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Http\Controller;

use App\ApiClient\Application\Query\GetApiClient\ApiClientResponse;
use App\ApiClient\Application\Query\GetApiClient\GetApiClientQuery;
use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api-clients/{id}', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/api-clients/{id}',
    operationId: 'getApiClient',
    summary: 'Get an OAuth2 machine client',
    description: 'Requires admin (`ROLE_ADMIN`). Never returns the secret — see the create/rotate responses for that.',
    tags: ['ApiClients'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 200, description: 'API client found')]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
#[OA\Response(response: 404, description: 'API client not found')]
final class GetApiClientController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        /** @var ApiClientResponse $response */
        $response = $this->queryBus->ask(new GetApiClientQuery($id));

        return $this->apiResponse->success($response);
    }
}
