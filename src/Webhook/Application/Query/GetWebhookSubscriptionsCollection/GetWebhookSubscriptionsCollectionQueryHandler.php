<?php

declare(strict_types=1);

namespace App\Webhook\Application\Query\GetWebhookSubscriptionsCollection;

use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetWebhookSubscriptionsCollectionQueryHandler
{
    public function __construct(
        private readonly WebhookSubscriptionRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetWebhookSubscriptionsCollectionQuery $query): WebhookSubscriptionsResponse
    {
        $entities = $this->repository->findByFilters($query->filters);
        $total = $this->repository->countByFilters($query->filters);

        return new WebhookSubscriptionsResponse(
            items: array_map(static fn ($e) => new WebhookSubscriptionItemResponse($e), $entities),
            total: $total,
            page: $query->filters->pagination->page,
            limit: $query->filters->pagination->limit,
        );
    }
}
