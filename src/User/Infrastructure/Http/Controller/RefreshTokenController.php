<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\LoginUser\LoginUserResponse;
use App\User\Application\Command\RefreshToken\RefreshTokenCommand;
use App\User\Infrastructure\Http\Request\RefreshTokenRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/refresh', methods: ['POST'])]
#[OA\Post(
    path: '/auth/refresh',
    summary: 'Refresh access and refresh tokens',
    description: 'Exchanges a valid, non-revoked, non-expired refresh token for a new pair. The previous refresh token is revoked (rotation). No `Authorization` header is required.',
    tags: ['Authentication'],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['refresh_token'],
        properties: [
            new OA\Property(
                property: 'refresh_token',
                type: 'string',
                description: 'Current refresh token',
                example: 'dGhpcy1pcy1hLXJlZnJlc2gtdG9rZW4='
            ),
        ]
    )
)]
#[OA\Response(
    response: 200,
    description: 'New tokens issued',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'data',
                type: 'object',
                properties: [
                    new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                    new OA\Property(property: 'access_token', type: 'string', example: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'),
                    new OA\Property(property: 'access_token_expires_in', type: 'integer', example: 3600),
                    new OA\Property(property: 'refresh_token', type: 'string', example: 'bmV3LXJlZnJlc2gtdG9rZW4='),
                    new OA\Property(property: 'refresh_token_expires_in', type: 'integer', example: 2592000),
                ]
            ),
        ]
    )
)]
#[OA\Response(response: 400, description: 'Invalid JSON, empty body, or missing `refresh_token`')]
#[OA\Response(
    response: 401,
    description: 'Unknown/revoked/expired refresh token, or user missing/inactive (see `error.code` in body)'
)]
final class RefreshTokenController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(RefreshTokenRequest $request): JsonResponse
    {
        /** @var LoginUserResponse $response */
        $response = $this->commandBus->dispatch(
            new RefreshTokenCommand(
                refreshToken: $request->refreshToken(),
            )
        );

        return $this->apiResponse->success([
            'access_token' => $response->accessToken,
            'access_token_expires_in' => $response->accessTokenExpiresIn,
            'refresh_token' => $response->refreshToken,
            'refresh_token_expires_in' => $response->refreshTokenExpiresIn,
            'token_type' => $response->tokenType,
        ]);
    }
}
