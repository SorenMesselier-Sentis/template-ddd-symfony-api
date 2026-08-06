<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Query\GetFeatureFlags\GetFeatureFlagsQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/feature-flags', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/feature-flags',
    operationId: 'getFeatureFlags',
    summary: 'List feature flags',
    description: 'Requires admin (`ROLE_ADMIN`).',
    tags: ['Feature flags'],
    security: [['bearer' => []]],
)]
#[OA\Response(
    response: 200,
    description: 'Feature flags',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'data',
                type: 'array',
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'key', type: 'string', example: 'cursor_pagination'),
                        new OA\Property(property: 'enabled', type: 'boolean', example: true),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    ),
)]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
final class GetFeatureFlagsController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $result = $this->queryBus->ask(new GetFeatureFlagsQuery());

        return $this->apiResponse->success($result->flags);
    }
}
