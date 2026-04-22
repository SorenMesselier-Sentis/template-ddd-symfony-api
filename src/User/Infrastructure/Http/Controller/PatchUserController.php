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
#[OA\Patch(path: '/api/v1/users/{id}', summary: 'Partially update a user', tags: ['Users'])]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'firstName', type: 'string'),
            new OA\Property(property: 'lastName', type: 'string'),
            new OA\Property(property: 'email', type: 'string'),
            new OA\Property(property: 'password', type: 'string'),
        ]
    )
)]
#[OA\Response(response: 204, description: 'User updated')]
#[OA\Response(response: 400, description: 'Empty patch or missing field')]
#[OA\Response(response: 404, description: 'User not found')]
final class PatchUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {}

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
