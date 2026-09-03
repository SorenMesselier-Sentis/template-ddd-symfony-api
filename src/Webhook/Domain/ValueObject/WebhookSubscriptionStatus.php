<?php

declare(strict_types=1);

namespace App\Webhook\Domain\ValueObject;

enum WebhookSubscriptionStatus: string
{
    case ACTIVE = 'active';
    case DISABLED = 'disabled';
    case DELETED = 'deleted';
}
