<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\LoginUser\LoginUserResponse;
use App\User\Application\Command\RefreshToken\RefreshTokenCommand;
use App\User\Infrastructure\Http\Request\RefreshTokenRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/refresh', methods: ['POST'])]
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
