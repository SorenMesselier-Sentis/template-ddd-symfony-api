<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\WithdrawConsent\WithdrawConsentCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/me/consents/{type}', methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/v1/users/me/consents/{type}',
    operationId: 'deleteMeConsent',
    summary: 'Withdraw the authenticated user consent for a given type',
    description: 'Idempotent — withdrawing a consent that was never given, or already withdrawn, '
        .'still returns 204. Recorded in the audit trail.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(
    name: 'type',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', enum: ['terms_of_service', 'privacy_policy', 'marketing_emails']),
)]
#[OA\Response(response: 204, description: 'Consent withdrawn (empty body)')]
final class DeleteMeConsentController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $type): JsonResponse
    {
        $this->commandBus->dispatch(new WithdrawConsentCommand($type));

        return $this->apiResponse->noContent();
    }
}
