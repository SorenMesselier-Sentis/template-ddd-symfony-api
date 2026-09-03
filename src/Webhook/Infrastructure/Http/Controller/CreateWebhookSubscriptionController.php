<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\Webhook\Application\Command\CreateWebhookSubscription\CreateWebhookSubscriptionCommand;
use App\Webhook\Infrastructure\Http\Request\CreateWebhookSubscriptionRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/webhook-subscriptions', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/webhook-subscriptions',
    operationId: 'postWebhookSubscriptions',
    summary: 'Register a webhook subscription',
    description: 'Requires admin (`ROLE_ADMIN`). `url` must be `https://` and not a private/loopback/link-local host. '
        .'The plain-text `secret` (used to sign delivered payloads) is returned only in this response — it cannot be '
        .'retrieved again, only rotated.',
    tags: ['Webhooks'],
    security: [['bearer' => []]],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['name', 'url', 'event_names'],
        properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Billing system sync'),
            new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://example.com/webhooks/inbound'),
            new OA\Property(property: 'event_names', type: 'array', items: new OA\Items(type: 'string'), example: ['document.uploaded', 'user.created']),
        ],
    ),
)]
#[OA\Response(
    response: 201,
    description: 'Subscription created',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'data',
                properties: [
                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'secret', type: 'string'),
                ],
                type: 'object',
            ),
        ],
    ),
)]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
#[OA\Response(response: 422, description: 'Invalid URL (not https, or targets a blocked host)')]
final class CreateWebhookSubscriptionController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(CreateWebhookSubscriptionRequest $request): JsonResponse
    {
        $id = Uuid::v4()->toRfc4122();

        /** @var array{id: string, secret: string} $result */
        $result = $this->commandBus->dispatch(new CreateWebhookSubscriptionCommand(
            id: $id,
            name: $request->name(),
            url: $request->url(),
            eventNames: $request->eventNames(),
        ));

        return $this->apiResponse->created($result);
    }
}
