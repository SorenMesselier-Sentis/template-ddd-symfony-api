<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Exception\EmptyPatchException;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\UpdateUser\UpdateUserCommand;
use App\User\Infrastructure\Http\Request\PatchUserRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/{id}', methods: ['PATCH'])]
#[OA\Patch(
    path: '/api/v1/users/{id}',
    operationId: 'patchUser',
    summary: 'Partially update a user',
    description: 'Updates only the fields sent in the body. At least one field is required.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'firstName', type: 'string', example: 'John'),
            new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
            new OA\Property(property: 'password', type: 'string', format: 'password', example: 'newsecret1234'),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'User updated (empty body)')]
#[OA\Response(response: 404, description: 'User not found')]
final class PatchUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id, PatchUserRequest $request): JsonResponse
    {
        if ($request->isEmpty()) {
            throw new EmptyPatchException('At least one field must be provided.');
        }

        $this->commandBus->dispatch(new UpdateUserCommand(
            id: $id,
            firstName: $request->firstName(),
            lastName: $request->lastName(),
            email: $request->email(),
            password: $request->password(),
        ));

        return $this->apiResponse->noContent();
    }
}
