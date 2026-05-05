<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Query\GetUser\GetUserQuery;
use App\User\Application\Query\GetUser\UserResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/{id}', methods: ['GET'])]
#[OA\Get(path: '/api/v1/users/{id}', summary: 'Get a user', tags: ['Users'])]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'User found',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'first_name', type: 'string'),
                new OA\Property(property: 'last_name', type: 'string'),
                new OA\Property(property: 'email', type: 'string'),
            ], type: 'object'),
        ]
    )
)]
#[OA\Response(response: 404, description: 'User not found')]
final class GetUserController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        /** @var UserResponse $user */
        $user = $this->queryBus->ask(new GetUserQuery($id));

        return $this->apiResponse->success($user);
    }
}
