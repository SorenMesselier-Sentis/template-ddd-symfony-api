<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\Webhook\Application\Command\DeleteWebhookSubscription\DeleteWebhookSubscriptionCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/webhook-subscriptions/{id}', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/v1/webhook-subscriptions/{id}',
    operationId: 'deleteWebhookSubscription',
    summary: 'Delete a webhook subscription',
    description: 'Requires admin (`ROLE_ADMIN`). Soft-deletes the subscription — unlike disable, this is not meant to be reversed.',
    tags: ['Webhooks'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 204, description: 'Subscription deleted')]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
#[OA\Response(response: 404, description: 'Subscription not found')]
final class DeleteWebhookSubscriptionController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteWebhookSubscriptionCommand($id));

        return $this->apiResponse->noContent();
    }
}
