<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Controller;

use App\Shared\Infrastructure\Health\HealthCheckRegistry;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health', name: 'health_check', methods: ['GET'])]
#[OA\Get(
    path: '/health',
    summary: 'Health check',
    description: 'Readiness probe for CI/CD and orchestrators. Returns 200 when all registered dependency checks are healthy, 503 when one fails. No authentication required.',
    tags: ['Infrastructure'],
    servers: [new OA\Server(url: 'http://localhost:8080')],
)]
#[OA\Response(
    response: 200,
    description: 'API dependencies are healthy',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'status', type: 'string', example: 'ok'),
                new OA\Property(
                    property: 'checks',
                    properties: [
                        new OA\Property(property: 'api', type: 'string', example: 'ok'),
                        new OA\Property(property: 'database', type: 'string', example: 'ok'),
                        new OA\Property(property: 'rabbitmq', type: 'string', example: 'ok'),
                    ],
                    type: 'object',
                ),
                new OA\Property(
                    property: 'checks_details',
                    properties: [
                        new OA\Property(
                            property: 'api',
                            properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'ok'),
                                new OA\Property(property: 'duration_ms', type: 'integer', example: 1),
                            ],
                            type: 'object',
                        ),
                        new OA\Property(
                            property: 'database',
                            properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'ok'),
                                new OA\Property(property: 'duration_ms', type: 'integer', example: 4),
                            ],
                            type: 'object',
                        ),
                        new OA\Property(
                            property: 'rabbitmq',
                            properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'ok'),
                                new OA\Property(property: 'duration_ms', type: 'integer', example: 3),
                            ],
                            type: 'object',
                        ),
                    ],
                    type: 'object',
                ),
            ], type: 'object'),
        ]
    )
)]
#[OA\Response(
    response: 503,
    description: 'At least one dependency is unreachable',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(
                    property: 'checks',
                    properties: [
                        new OA\Property(property: 'api', type: 'string', example: 'ok'),
                        new OA\Property(property: 'database', type: 'string', example: 'ok'),
                        new OA\Property(property: 'rabbitmq', type: 'string', example: 'error'),
                    ],
                    type: 'object',
                ),
                new OA\Property(
                    property: 'checks_details',
                    properties: [
                        new OA\Property(
                            property: 'rabbitmq',
                            properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'error'),
                                new OA\Property(property: 'duration_ms', type: 'integer', example: 5),
                                new OA\Property(property: 'detail', type: 'string', example: 'Connection refused'),
                            ],
                            type: 'object',
                        ),
                    ],
                    type: 'object',
                ),
            ], type: 'object'),
        ]
    )
)]
final class HealthCheckController
{
    public function __construct(
        private readonly HealthCheckRegistry $registry,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $result = $this->registry->run();

        return $this->apiResponse->success(
            data: [
                'status' => $result->status->state()->value,
                'checks' => $result->checks,
                'checks_details' => $result->checksDetails,
            ],
            status: $result->httpStatusCode(),
        );
    }
}
