<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Exception\EmptyPatchException;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\UpdateUser\UpdateUserCommand;
use App\User\Infrastructure\Http\Request\PatchUserRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/{id}', methods: ['PATCH'])]
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
