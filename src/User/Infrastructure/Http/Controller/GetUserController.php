<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Query\GetUser\GetUserQuery;
use App\User\Application\Query\GetUser\UserResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/{id}', methods: ['GET'])]
final class GetUserController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        /** @var UserResponse $user */
        $user = $this->queryBus->ask(new GetUserQuery($id));

        return $this->apiResponse->success($user);
    }
}
