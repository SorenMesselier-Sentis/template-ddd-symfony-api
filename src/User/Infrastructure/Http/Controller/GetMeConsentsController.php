<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Query\GetMyConsents\GetMyConsentsQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/me/consents', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/users/me/consents',
    operationId: 'getMeConsents',
    summary: 'List the authenticated user consent records',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\Response(response: 200, description: 'Consent records')]
final class GetMeConsentsController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $consents = $this->queryBus->ask(new GetMyConsentsQuery());

        return $this->apiResponse->success($consents);
    }
}
