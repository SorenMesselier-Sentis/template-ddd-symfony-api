<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Query\QueryBusInterface;
use App\Shared\Infrastructure\Http\Filter\FiltersBuilder;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\Webhook\Application\Query\GetWebhookSubscriptionsCollection\GetWebhookSubscriptionsCollectionQuery;
use App\Webhook\Application\Query\GetWebhookSubscriptionsCollection\WebhookSubscriptionsResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhook-subscriptions', methods: ['GET'])]
#[OA\Get(
    path: '/api/v1/webhook-subscriptions',
    operationId: 'getWebhookSubscriptions',
    summary: 'List webhook subscriptions',
    description: 'Requires admin (`ROLE_ADMIN`). Returns a paginated list. Supports `page`, `limit`, and filter `status`.',
    tags: ['Webhooks'],
    security: [['bearer' => []]],
)]
#[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1))]
#[OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100, minimum: 1))]
#[OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'disabled']))]
#[OA\Response(response: 200, description: 'Paginated list of webhook subscriptions')]
#[OA\Response(response: 403, description: 'Insufficient privileges')]
final class ListWebhookSubscriptionsController
{
    private const ALLOWED_FILTERS = [
        'status' => 'equal',
    ];

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ApiResponse $apiResponse,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $filters = FiltersBuilder::fromRequest($request, self::ALLOWED_FILTERS);

        /** @var WebhookSubscriptionsResponse $result */
        $result = $this->queryBus->ask(new GetWebhookSubscriptionsCollectionQuery($filters));

        return $this->apiResponse->paginated(
            data: $result->items,
            total: $result->total,
            page: $result->page,
            limit: $result->limit,
            request: $request,
        );
    }
}
