<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\RequestPasswordReset\RequestPasswordResetCommand;
use App\User\Infrastructure\Http\Request\ForgotPasswordRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/forgot-password', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/auth/forgot-password',
    operationId: 'postAuthForgotPassword',
    summary: 'Request password reset',
    description: 'Sends a password reset email if the account exists. Always returns 204 to prevent email enumeration.',
    tags: ['Authentication'],
    security: [],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['email'],
        properties: [
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'Request accepted')]
final class ForgotPasswordController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(
            new RequestPasswordResetCommand(Email::fromString($request->email()))
        );

        return $this->apiResponse->noContent();
    }
}
