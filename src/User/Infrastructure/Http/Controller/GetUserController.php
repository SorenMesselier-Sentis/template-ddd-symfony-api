<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\User\Application\Query\GetUser\GetUserQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetUserController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    #[Route('/users/{id}', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        $user = $this->queryBus->ask(new GetUserQuery($id));

        return new JsonResponse([
            'id' => $user->id,
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'email' => $user->email,
        ]);
    }
}
