<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\Webhook\Application\Command\DisableWebhookSubscription\DisableWebhookSubscriptionCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhook-subscriptions/{id}/disable', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/webhook-subscriptions/{id}/disable',
    operationId: 'postWebhookSubscriptionDisable',
    summary: 'Disable a webhook subscription',
    description: 'Requires admin (`ROLE_ADMIN`). Stops deliveries immediately without deleting the subscription — reversible via the enable endpoint.',
    tags: ['Webhooks'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 204, description: 'Subscription disabled')]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
#[OA\Response(response: 404, description: 'Subscription not found')]
final class DisableWebhookSubscriptionController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DisableWebhookSubscriptionCommand($id));

        return $this->apiResponse->noContent();
    }
}
