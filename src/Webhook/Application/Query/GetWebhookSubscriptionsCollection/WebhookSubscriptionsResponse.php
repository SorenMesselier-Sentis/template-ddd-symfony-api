<?php

declare(strict_types=1);

namespace App\Webhook\Application\Query\GetWebhookSubscriptionsCollection;

use App\Shared\Domain\Bus\Query\Response;

final class WebhookSubscriptionsResponse implements Response
{
    /**
     * @param list<WebhookSubscriptionItemResponse> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit,
    ) {
    }
}
