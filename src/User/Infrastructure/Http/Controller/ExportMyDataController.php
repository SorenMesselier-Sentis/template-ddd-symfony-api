<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Query\ExportUserData\ExportUserDataQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/me/export', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/users/me/export',
    operationId: 'exportMyData',
    summary: 'Export all personal data held about the current user',
    description: 'GDPR right of access / data portability: aggregates the personal data every bounded context holds '
        .'about the authenticated user (profile, documents, ...) into a single downloadable export.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\Response(response: 200, description: 'Personal data export')]
#[OA\Response(response: 401, description: 'Unauthenticated')]
final class ExportMyDataController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $result = $this->queryBus->ask(new ExportUserDataQuery());

        $response = $this->apiResponse->success([
            'exported_at' => $result->exportedAt,
            ...$result->data,
        ]);
        $response->headers->set('Content-Disposition', 'attachment; filename="personal-data-export.json"');

        return $response;
    }
}
