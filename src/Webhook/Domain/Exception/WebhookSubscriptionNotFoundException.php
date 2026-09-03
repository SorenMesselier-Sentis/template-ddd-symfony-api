<?php

declare(strict_types=1);

namespace App\Webhook\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class WebhookSubscriptionNotFoundException extends NotFoundException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Webhook subscription with id "%s" was not found.', $id));
    }

    public function errorCode(): string
    {
        return 'webhook_subscription.not_found';
    }
}
