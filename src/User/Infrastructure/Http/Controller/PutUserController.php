<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\ReplaceUser\ReplaceUserCommand;
use App\User\Infrastructure\Http\Request\PutUserRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/{id}', methods: ['PUT'])]
#[OA\Put(
    path: '/api/v1/users/{id}',
    operationId: 'putUser',
    summary: 'Replace a user',
    description: 'Replaces all scalar fields of the user.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['firstName', 'lastName', 'email', 'password'],
        properties: [
            new OA\Property(property: 'firstName', type: 'string', example: 'John'),
            new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
            new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret1234'),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'User replaced (empty body)')]
#[OA\Response(response: 404, description: 'User not found')]
final class PutUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id, PutUserRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new ReplaceUserCommand(
            id: $id,
            firstName: $request->firstName(),
            lastName: $request->lastName(),
            email: $request->email(),
            password: $request->password(),
        ));

        return $this->apiResponse->noContent();
    }
}
