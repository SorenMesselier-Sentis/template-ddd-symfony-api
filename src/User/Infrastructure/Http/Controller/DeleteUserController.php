<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\DeleteUser\DeleteUserCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/{id}', methods: ['DELETE'])]
final class DeleteUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteUserCommand($id));

        return $this->apiResponse->noContent();
    }
}
