<?php

declare(strict_types=1);

namespace App\Webhook\Application\Query\GetWebhookSubscription;

use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Security\RoleRequirement;
use App\Webhook\Application\Security\AuthorizedMessage;

/** @implements Query<WebhookSubscriptionResponse> */
final class GetWebhookSubscriptionQuery implements Query, AuthorizedMessage
{
    public function __construct(
        public readonly string $id,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }
}
