<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\Webhook\Application\Command\EnableWebhookSubscription\EnableWebhookSubscriptionCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhook-subscriptions/{id}/enable', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/webhook-subscriptions/{id}/enable',
    operationId: 'postWebhookSubscriptionEnable',
    summary: 'Re-enable a disabled webhook subscription',
    description: 'Requires admin (`ROLE_ADMIN`). Resumes deliveries.',
    tags: ['Webhooks'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 204, description: 'Subscription enabled')]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
#[OA\Response(response: 404, description: 'Subscription not found')]
final class EnableWebhookSubscriptionController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new EnableWebhookSubscriptionCommand($id));

        return $this->apiResponse->noContent();
    }
}
