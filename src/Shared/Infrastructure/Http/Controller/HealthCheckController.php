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
    description: 'Liveness/readiness probe for CI/CD and orchestrators. Returns 200 when the API and database are up, 503 when the database check fails. No authentication required.',
    tags: ['Infrastructure'],
    servers: [new OA\Server(url: 'http://localhost:8080')],
)]
#[OA\Response(
    response: 200,
    description: 'API and database are healthy',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'status', type: 'string', example: 'ok'),
                new OA\Property(
                    property: 'checks',
                    properties: [
                        new OA\Property(property: 'api', type: 'string', example: 'ok'),
                        new OA\Property(property: 'database', type: 'string', example: 'ok'),
                    ],
                    type: 'object',
                ),
            ], type: 'object'),
        ]
    )
)]
#[OA\Response(
    response: 503,
    description: 'Database unreachable',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(
                    property: 'checks',
                    properties: [
                        new OA\Property(property: 'api', type: 'string', example: 'ok'),
                        new OA\Property(property: 'database', type: 'string', example: 'error'),
                    ],
                    type: 'object',
                ),
            ], type: 'object'),
        ]
    )
)]
final class HealthCheckController
{
    private const DEBUG_LOG_PATH = '/Users/taranis/Developer/Project/Templates/template-ddd-symfony/.cursor/debug-170cf4.log';
    private const DEBUG_SESSION_ID = '170cf4';
    private const DEBUG_RUN_ID = 'initial';

    public function __construct(
        private readonly HealthCheckRegistry $registry,
        private readonly ApiResponse $apiResponse,
    ) {
        // #region agent log
        $this->debugLog(
            hypothesisId: 'H3',
            location: 'HealthCheckController.php:69',
            message: 'HealthCheckController constructed',
            data: [],
        );
        // #endregion
    }

    public function __invoke(): JsonResponse
    {
        // #region agent log
        $this->debugLog(
            hypothesisId: 'H4',
            location: 'HealthCheckController.php:80',
            message: 'Health endpoint invoked',
            data: [],
        );
        // #endregion

        $result = $this->registry->run();

        return $this->apiResponse->success(
            data: [
                'status' => $result->status,
                'checks' => $result->checks,
            ],
            status: $result->httpStatusCode(),
        );
    }

    private function debugLog(string $hypothesisId, string $location, string $message, array $data): void
    {
        @file_put_contents(
            self::DEBUG_LOG_PATH,
            (string) json_encode([
                'sessionId' => self::DEBUG_SESSION_ID,
                'runId' => self::DEBUG_RUN_ID,
                'hypothesisId' => $hypothesisId,
                'location' => $location,
                'message' => $message,
                'data' => $data,
                'timestamp' => (int) (microtime(true) * 1000),
            ]) . PHP_EOL,
            FILE_APPEND,
        );
    }
}
