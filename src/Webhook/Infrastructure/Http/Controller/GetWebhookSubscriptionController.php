<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\Webhook\Application\Query\GetWebhookSubscription\GetWebhookSubscriptionQuery;
use App\Webhook\Application\Query\GetWebhookSubscription\WebhookSubscriptionResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhook-subscriptions/{id}', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/webhook-subscriptions/{id}',
    operationId: 'getWebhookSubscription',
    summary: 'Get a webhook subscription',
    description: 'Requires admin (`ROLE_ADMIN`). Never returns the signing secret.',
    tags: ['Webhooks'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(response: 200, description: 'Subscription found')]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
#[OA\Response(response: 404, description: 'Subscription not found')]
final class GetWebhookSubscriptionController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        /** @var WebhookSubscriptionResponse $response */
        $response = $this->queryBus->ask(new GetWebhookSubscriptionQuery($id));

        return $this->apiResponse->success($response);
    }
}
