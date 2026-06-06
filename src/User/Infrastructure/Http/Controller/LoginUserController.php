<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\LoginUser\LoginUserCommand;
use App\User\Infrastructure\Http\Request\LoginUserRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/login', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/auth/login',
    operationId: 'postAuthLogin',
    summary: 'Login',
    description: 'Authenticates with email and password. Returns a JWT access token and a refresh token. No `Authorization` header required.',
    tags: ['Authentication'],
    security: [],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['email', 'password'],
        properties: [
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
            new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret1234'),
        ],
        example: ['email' => 'john.doe@example.com', 'password' => 'secret1234'],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Authenticated successfully',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'data',
                properties: [
                    new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                    new OA\Property(property: 'access_token', type: 'string', example: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'),
                    new OA\Property(property: 'access_token_expires_in', type: 'integer', example: 3600),
                    new OA\Property(property: 'refresh_token', type: 'string', example: 'dGhpcy1pcy1hLXJlZnJlc2gtdG9rZW4='),
                    new OA\Property(property: 'refresh_token_expires_in', type: 'integer', example: 2592000),
                ],
                type: 'object',
            ),
        ],
    ),
)]
#[OA\Response(
    response: 422,
    description: 'Validation failed',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'error',
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: 'validation_error'),
                    new OA\Property(property: 'message', type: 'string', example: 'Validation failed.'),
                ],
                type: 'object',
            ),
        ],
    ),
)]
#[OA\Response(
    response: 401,
    description: 'Invalid credentials',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'error',
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: 'authentication.invalid_credentials'),
                    new OA\Property(property: 'message', type: 'string', example: 'Invalid email or password.'),
                ],
                type: 'object',
            ),
        ],
    ),
)]
final class LoginUserController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(LoginUserRequest $request): JsonResponse
    {
        $email = Email::fromString($request->email());

        $response = $this->commandBus->dispatch(
            new LoginUserCommand(
                $email,
                $request->password(),
            )
        );

        return $this->apiResponse->success([
            'token_type' => $response->tokenType,
            'access_token' => $response->accessToken,
            'access_token_expires_in' => $response->accessTokenExpiresIn,
            'refresh_token' => $response->refreshToken,
            'refresh_token_expires_in' => $response->refreshTokenExpiresIn,
        ]);
    }
}
