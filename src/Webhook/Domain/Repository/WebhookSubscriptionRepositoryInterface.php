<?php

declare(strict_types=1);

namespace App\Webhook\Domain\Repository;

use App\Shared\Domain\Filter\Filters;
use App\Webhook\Domain\Entity\WebhookSubscription;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;

interface WebhookSubscriptionRepositoryInterface
{
    public function save(WebhookSubscription $subscription): void;

    /**
     * Excludes soft-deleted subscriptions.
     */
    public function findById(WebhookSubscriptionId $id): ?WebhookSubscription;

    public function findByIdIncludingDeleted(WebhookSubscriptionId $id): ?WebhookSubscription;

    /**
     * Active subscriptions whose `eventNames` includes `$eventName` — the query
     * DispatchWebhooksOnAnyDomainEvent runs for every domain event published anywhere in the app.
     *
     * @return list<WebhookSubscription>
     */
    public function findActiveByEventName(string $eventName): array;

    /**
     * @return list<WebhookSubscription>
     */
    public function findByFilters(Filters $filters): array;

    public function countByFilters(Filters $filters): int;
}
