<?php

declare(strict_types=1);

namespace App\Webhook\Application\Query\GetWebhookSubscriptionsCollection;

use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Security\RoleRequirement;
use App\Webhook\Application\Security\AuthorizedMessage;

/** @implements Query<WebhookSubscriptionsResponse> */
final class GetWebhookSubscriptionsCollectionQuery implements Query, AuthorizedMessage
{
    public function __construct(
        public readonly Filters $filters,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }
}
