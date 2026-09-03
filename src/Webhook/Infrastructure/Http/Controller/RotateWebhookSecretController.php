<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\Webhook\Application\Command\RotateWebhookSecret\RotateWebhookSecretCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhook-subscriptions/{id}/rotate-secret', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/webhook-subscriptions/{id}/rotate-secret',
    operationId: 'postWebhookSubscriptionRotateSecret',
    summary: 'Rotate a webhook subscription\'s signing secret',
    description: 'Requires admin (`ROLE_ADMIN`). The new plain-text `secret` is returned only in this response.',
    tags: ['Webhooks'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'Secret rotated',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'data',
                properties: [new OA\Property(property: 'secret', type: 'string')],
                type: 'object',
            ),
        ],
    ),
)]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
#[OA\Response(response: 404, description: 'Subscription not found')]
final class RotateWebhookSecretController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        /** @var array{secret: string} $result */
        $result = $this->commandBus->dispatch(new RotateWebhookSecretCommand($id));

        return $this->apiResponse->success($result);
    }
}
