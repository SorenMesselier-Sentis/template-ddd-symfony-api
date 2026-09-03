<?php

declare(strict_types=1);

namespace App\Tests\Unit\Webhook\Domain\Mother;

use App\Webhook\Domain\Entity\WebhookSubscription;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use App\Webhook\Domain\ValueObject\WebhookUrl;

final class WebhookSubscriptionMother
{
    /**
     * @param list<string> $eventNames
     */
    public static function create(
        ?WebhookSubscriptionId $id = null,
        string $name = 'Test Subscription',
        ?WebhookUrl $url = null,
        string $secret = 'test-secret',
        array $eventNames = ['document.uploaded'],
    ): WebhookSubscription {
        return WebhookSubscription::create(
            id: $id ?? WebhookSubscriptionId::random(),
            name: $name,
            url: $url ?? WebhookUrl::fromString('https://example.com/inbound'),
            secret: $secret,
            eventNames: $eventNames,
        );
    }
}
