<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\DeactivateUser\DeactivateUserCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/users/{id}/deactivate', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/users/{id}/deactivate',
    operationId: 'postUserDeactivate',
    summary: 'Deactivate a user',
    description: 'Deactivates a user account and revokes all refresh tokens. Requires admin role.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 204, description: 'User deactivated')]
#[OA\Response(response: 404, description: 'User not found')]
final class DeactivateUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeactivateUserCommand($id));

        return $this->apiResponse->noContent();
    }
}
