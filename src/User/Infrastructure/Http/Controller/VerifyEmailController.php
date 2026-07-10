<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\VerifyEmail\VerifyEmailCommand;
use App\User\Infrastructure\Http\Request\VerifyEmailRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/verify-email', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/auth/verify-email',
    operationId: 'postAuthVerifyEmail',
    summary: 'Verify email address',
    description: 'Verifies the user email using a verification token.',
    tags: ['Authentication'],
    security: [],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['token'],
        properties: [
            new OA\Property(property: 'token', type: 'string'),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'Email verified')]
#[OA\Response(response: 401, description: 'Invalid or expired token')]
final class VerifyEmailController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(VerifyEmailRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new VerifyEmailCommand($request->token()));

        return $this->apiResponse->noContent();
    }
}
