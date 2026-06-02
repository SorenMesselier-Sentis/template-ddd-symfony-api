<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\UpdateUserRoles\UpdateUserRolesCommand;
use App\User\Infrastructure\Http\Request\UpdateUserRolesRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/{id}/roles', methods: ['PUT'])]
#[OA\Put(
    path: '/api/v1/users/{id}/roles',
    operationId: 'putUserRoles',
    summary: 'Update user roles',
    description: 'Replaces the roles of a user. Requires admin (`ROLE_ADMIN`). `ROLE_USER` is appended if missing.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['roles'],
        properties: [
            new OA\Property(
                property: 'roles',
                type: 'array',
                items: new OA\Items(type: 'string', enum: ['ROLE_USER', 'ROLE_ADMIN']),
                example: ['ROLE_ADMIN', 'ROLE_USER'],
            ),
        ],
        example: ['roles' => ['ROLE_ADMIN', 'ROLE_USER']],
    ),
)]
#[OA\Response(response: 204, description: 'Roles updated (empty body)')]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
#[OA\Response(response: 404, description: 'User not found')]
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
