<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\User\Application\Command\RecordConsent\RecordConsentCommand;
use App\User\Infrastructure\Http\Request\PostMeConsentRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users/me/consents', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/users/me/consents',
    operationId: 'postMeConsent',
    summary: 'Record (or renew) the authenticated user consent for a given type',
    description: 'Upserts one consent record per (user, type) — recording it again with a new '
        .'version renews it. Recorded in the audit trail.',
    tags: ['Users'],
    security: [['bearer' => []]],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['type', 'version'],
        properties: [
            new OA\Property(
                property: 'type',
                type: 'string',
                enum: ['terms_of_service', 'privacy_policy', 'marketing_emails'],
            ),
            new OA\Property(property: 'version', type: 'string', example: '2026-09-16'),
        ],
    ),
)]
#[OA\Response(response: 201, description: 'Consent recorded')]
final class PostMeConsentController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(PostMeConsentRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new RecordConsentCommand(
            type: $request->type(),
            version: $request->version(),
        ));

        return $this->apiResponse->created();
    }
}
