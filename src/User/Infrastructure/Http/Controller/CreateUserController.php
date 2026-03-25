<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\CreateUser\CreateUserCommand;
use App\User\Infrastructure\Http\Request\CreateUserRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/users', methods: ['POST'])]
final class CreateUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(CreateUserRequest $request): JsonResponse
    {
        $id = Uuid::v4()->toRfc4122();

        $this->commandBus->dispatch(new CreateUserCommand(
            id: $id,
            firstName: $request->firstName(),
            lastName: $request->lastName(),
            email: $request->email(),
            password: $request->password(),
        ));

        return ApiResponse::created(['id' => $id]);
    }
}
