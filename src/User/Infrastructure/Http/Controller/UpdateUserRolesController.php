<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\UpdateUserRoles\UpdateUserRolesCommand;
use App\User\Infrastructure\Http\Request\UpdateUserRolesRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/{id}/roles', methods: ['PUT'])]
final class UpdateUserRolesController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id, UpdateUserRolesRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new UpdateUserRolesCommand(
            id: $id,
            roles: $request->roles(),
        ));

        return $this->apiResponse->noContent();
    }
}
