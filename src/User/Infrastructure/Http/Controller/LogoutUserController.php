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
    summary: 'Logout (revoke a refresh token)',
    description: 'Requires a valid access token in `Authorization` and the refresh token to revoke in the body. Returns 204 with an empty body on success.',
    tags: ['Authentication'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['refresh_token'],
            properties: [
                new OA\Property(
                    property: 'refresh_token',
                    type: 'string',
                    description: 'Refresh token to revoke (opaque string issued at login or refresh)',
                    example: 'dGhpcy1pcy1hLXJlZnJlc2gtdG9rZW4='
                ),
            ]
        )
    ),
)]
#[OA\Parameter(
    name: 'Authorization',
    in: 'header',
    required: true,
    description: 'Bearer access token (JWT)',
    schema: new OA\Schema(type: 'string', example: 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...')
)]
#[OA\Response(response: 204, description: 'Refresh token revoked; response body is empty')]
#[OA\Response(response: 400, description: 'Invalid JSON, empty body, or missing `refresh_token`')]
#[OA\Response(response: 401, description: 'Missing/invalid/expired access token (Bearer JWT)')]
#[OA\Response(response: 404, description: 'Refresh token not found in storage')]
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
