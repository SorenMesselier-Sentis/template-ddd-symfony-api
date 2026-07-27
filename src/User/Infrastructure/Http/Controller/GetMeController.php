<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Query\GetMe\GetMeQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/me', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/users/me',
    operationId: 'getMe',
    summary: 'Get current user profile',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\Response(response: 200, description: 'Current user profile')]
final class GetMeController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $user = $this->queryBus->ask(new GetMeQuery());

        return $this->apiResponse->success($user);
    }
}
