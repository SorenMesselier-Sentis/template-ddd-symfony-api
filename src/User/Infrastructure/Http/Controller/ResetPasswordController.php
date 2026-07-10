<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\ResetPassword\ResetPasswordCommand;
use App\User\Infrastructure\Http\Request\ResetPasswordRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/reset-password', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/auth/reset-password',
    operationId: 'postAuthResetPassword',
    summary: 'Reset password',
    description: 'Resets the password using a valid reset token.',
    tags: ['Authentication'],
    security: [],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['token', 'password'],
        properties: [
            new OA\Property(property: 'token', type: 'string'),
            new OA\Property(property: 'password', type: 'string', format: 'password'),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'Password reset successfully')]
#[OA\Response(response: 401, description: 'Invalid or expired token')]
final class ResetPasswordController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new ResetPasswordCommand(
            token: $request->token(),
            password: $request->password(),
        ));

        return $this->apiResponse->noContent();
    }
}
