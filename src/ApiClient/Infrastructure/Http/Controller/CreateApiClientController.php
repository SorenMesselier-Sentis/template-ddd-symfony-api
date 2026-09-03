<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Http\Controller;

use App\ApiClient\Application\Command\CreateApiClient\CreateApiClientCommand;
use App\ApiClient\Infrastructure\Http\Request\CreateApiClientRequest;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api-clients', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/api-clients',
    operationId: 'postApiClients',
    summary: 'Register a new OAuth2 machine client',
    description: 'Requires admin (`ROLE_ADMIN`). The plain-text `secret` is returned only in this response — it cannot be retrieved again, only rotated.',
    tags: ['ApiClients'],
    security: [['bearer' => []]],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['name'],
        properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Billing sync worker'),
            new OA\Property(property: 'scopes', type: 'array', items: new OA\Items(type: 'string'), example: ['documents:write']),
        ],
    ),
)]
#[OA\Response(
    response: 201,
    description: 'API client created',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'data',
                properties: [
                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'secret', type: 'string'),
                ],
                type: 'object',
            ),
        ],
    ),
)]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
final class CreateApiClientController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(CreateApiClientRequest $request): JsonResponse
    {
        $id = Uuid::v4()->toRfc4122();

        /** @var array{id: string, secret: string} $result */
        $result = $this->commandBus->dispatch(new CreateApiClientCommand(
            id: $id,
            name: $request->name(),
            scopes: $request->scopes(),
        ));

        return $this->apiResponse->created($result);
    }
}
