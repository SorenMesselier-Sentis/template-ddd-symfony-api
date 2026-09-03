<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\Webhook\Application\Command\UpdateWebhookSubscription\UpdateWebhookSubscriptionCommand;
use App\Webhook\Infrastructure\Http\Request\UpdateWebhookSubscriptionRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhook-subscriptions/{id}', methods: ['PUT'])]
#[OA\Put(
    path: '/api/v1/webhook-subscriptions/{id}',
    operationId: 'putWebhookSubscription',
    summary: 'Replace a webhook subscription\'s name, URL and subscribed events',
    description: 'Requires admin (`ROLE_ADMIN`). Does not touch the signing secret — see the rotate-secret endpoint for that.',
    tags: ['Webhooks'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['name', 'url', 'event_names'],
        properties: [
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'url', type: 'string', format: 'uri'),
            new OA\Property(property: 'event_names', type: 'array', items: new OA\Items(type: 'string')),
        ],
    ),
)]
#[OA\Response(response: 204, description: 'Subscription updated')]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
#[OA\Response(response: 404, description: 'Subscription not found')]
#[OA\Response(response: 422, description: 'Invalid URL (not https, or targets a blocked host)')]
final class UpdateWebhookSubscriptionController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id, UpdateWebhookSubscriptionRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new UpdateWebhookSubscriptionCommand(
            id: $id,
            name: $request->name(),
            url: $request->url(),
            eventNames: $request->eventNames(),
        ));

        return $this->apiResponse->noContent();
    }
}
