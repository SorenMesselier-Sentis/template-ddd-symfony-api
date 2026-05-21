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
    path: '/users/{id}/roles',
    summary: 'Update user roles',
    description: 'Replaces the roles of a user. Requires a valid admin access token (`ROLE_ADMIN`). `ROLE_USER` is always appended if missing.',
    tags: ['Users'],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Parameter(
    name: 'Authorization',
    in: 'header',
    required: true,
    description: 'Bearer access token (JWT) with `ROLE_ADMIN`',
    schema: new OA\Schema(type: 'string', example: 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...')
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['roles'],
        properties: [
            new OA\Property(
                property: 'roles',
                type: 'array',
                items: new OA\Items(
                    type: 'string',
                    enum: ['ROLE_USER', 'ROLE_ADMIN'],
                ),
                example: ['ROLE_ADMIN', 'ROLE_USER'],
            ),
        ]
    )
)]
#[OA\Response(response: 204, description: 'User roles updated; response body is empty')]
#[OA\Response(response: 400, description: 'Invalid JSON or missing/invalid `roles` field')]
#[OA\Response(response: 401, description: 'Missing/invalid/expired access token (Bearer JWT)')]
#[OA\Response(response: 403, description: 'Insufficient privileges (admin role required)')]
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
