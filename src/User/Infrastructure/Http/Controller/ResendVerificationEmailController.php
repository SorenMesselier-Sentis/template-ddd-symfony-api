<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\ResendVerificationEmail\ResendVerificationEmailCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/resend-verification-email', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/auth/resend-verification-email',
    operationId: 'postAuthResendVerificationEmail',
    summary: 'Resend verification email',
    description: 'Resends the email verification link to the authenticated user.',
    tags: ['Authentication'],
    security: [['bearer' => []]],
)]
#[OA\Response(response: 204, description: 'Verification email sent')]
final class ResendVerificationEmailController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $this->commandBus->dispatch(new ResendVerificationEmailCommand());

        return $this->apiResponse->noContent();
    }
}
