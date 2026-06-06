<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\LogoutUser\LogoutUserCommand;
use App\User\Infrastructure\Http\Request\LogoutUserRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/logout', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/auth/logout',
    operationId: 'postAuthLogout',
    summary: 'Logout',
    description: 'Revokes the given refresh token. Requires a valid access token in `Authorization`.',
    tags: ['Authentication'],
    security: [['bearer' => []]],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['refresh_token'],
        properties: [
            new OA\Property(
                property: 'refresh_token',
                type: 'string',
                description: 'Refresh token to revoke',
                example: 'dGhpcy1pcy1hLXJlZnJlc2gtdG9rZW4=',
            ),
        ],
        example: ['refresh_token' => 'dGhpcy1pcy1hLXJlZnJlc2gtdG9rZW4='],
    ),
)]
#[OA\Response(response: 204, description: 'Refresh token revoked (empty body)')]
#[OA\Response(response: 401, description: 'Missing or invalid access token')]
#[OA\Response(response: 404, description: 'Refresh token not found')]
final class LogoutUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(LogoutUserRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(
            new LogoutUserCommand(
                refreshToken: $request->refreshToken(),
            )
        );

        return $this->apiResponse->noContent();
    }
}
