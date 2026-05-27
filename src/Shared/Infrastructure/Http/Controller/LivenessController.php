<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Controller;

use App\Shared\Infrastructure\Http\Response\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health/live', name: 'health_liveness', methods: ['GET'])]
#[OA\Get(
    path: '/health/live',
    summary: 'Liveness probe',
    description: 'The Orchestrator liveness probe. Returns 200 as long as PHP can answer this request. No external dependency check is performed.',
    tags: ['Infrastructure'],
    servers: [new OA\Server(url: 'http://localhost:8080')],
)]
#[OA\Response(
    response: 200,
    description: 'Process is alive',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'status', type: 'string', example: 'ok'),
            ], type: 'object'),
        ]
    )
)]
final class LivenessController
{
    public function __construct(
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return $this->apiResponse->success(data: ['status' => 'ok']);
    }
}
