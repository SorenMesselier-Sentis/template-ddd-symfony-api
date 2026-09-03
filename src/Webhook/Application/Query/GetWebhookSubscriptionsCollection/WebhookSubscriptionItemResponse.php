<?php

declare(strict_types=1);

namespace App\Webhook\Application\Query\GetWebhookSubscriptionsCollection;

use App\Shared\Domain\Bus\Query\Response;
use App\Webhook\Domain\Entity\WebhookSubscription;

final class WebhookSubscriptionItemResponse implements Response
{
    public readonly string $id;
    public readonly string $name;
    public readonly string $url;
    /** @var list<string> */
    public readonly array $eventNames;
    public readonly string $status;
    public readonly string $createdAt;

    public function __construct(WebhookSubscription $entity)
    {
        $this->id = $entity->id()->value();
        $this->name = $entity->name();
        $this->url = $entity->url()->value();
        $this->eventNames = $entity->eventNames();
        $this->status = $entity->status()->value;
        $this->createdAt = $entity->createdAt()->format(\DateTimeInterface::ATOM);
    }
}
