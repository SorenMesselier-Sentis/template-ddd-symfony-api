<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\ActivateUser\ActivateUserCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/users/{id}/activate', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/users/{id}/activate',
    operationId: 'postUserActivate',
    summary: 'Activate a user',
    description: 'Activates a user account. Requires admin role.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 204, description: 'User activated')]
#[OA\Response(response: 404, description: 'User not found')]
final class ActivateUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new ActivateUserCommand($id));

        return $this->apiResponse->noContent();
    }
}
