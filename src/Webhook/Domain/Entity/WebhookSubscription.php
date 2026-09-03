<?php

declare(strict_types=1);

namespace App\Webhook\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Webhook\Domain\Event\WebhookSubscriptionCreated;
use App\Webhook\Domain\Event\WebhookSubscriptionDeleted;
use App\Webhook\Domain\Event\WebhookSubscriptionDisabled;
use App\Webhook\Domain\Event\WebhookSubscriptionEnabled;
use App\Webhook\Domain\Event\WebhookSubscriptionSecretRotated;
use App\Webhook\Domain\Event\WebhookSubscriptionUpdated;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionStatus;
use App\Webhook\Domain\ValueObject\WebhookUrl;

final class WebhookSubscription
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    /**
     * @param list<string> $eventNames
     */
    private function __construct(
        private readonly WebhookSubscriptionId $id,
        private string $name,
        private WebhookUrl $url,
        private string $secret,
        private array $eventNames,
        private WebhookSubscriptionStatus $status,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * @param list<string> $eventNames
     */
    public static function create(
        WebhookSubscriptionId $id,
        string $name,
        WebhookUrl $url,
        string $secret,
        array $eventNames,
    ): self {
        $now = new \DateTimeImmutable();
        $entity = new self(
            id: $id,
            name: $name,
            url: $url,
            secret: $secret,
            eventNames: $eventNames,
            status: WebhookSubscriptionStatus::ACTIVE,
            createdAt: $now,
            updatedAt: $now,
        );

        $entity->record(new WebhookSubscriptionCreated(aggregateId: $id->value()));

        return $entity;
    }

    /**
     * @param list<string> $eventNames
     */
    public function update(string $name, WebhookUrl $url, array $eventNames): void
    {
        $this->name = $name;
        $this->url = $url;
        $this->eventNames = $eventNames;
        $this->touch();

        $this->record(new WebhookSubscriptionUpdated($this->id->value()));
    }

    public function rotateSecret(string $secret): void
    {
        $this->secret = $secret;
        $this->touch();

        $this->record(new WebhookSubscriptionSecretRotated($this->id->value()));
    }

    public function disable(): void
    {
        $this->status = WebhookSubscriptionStatus::DISABLED;
        $this->touch();

        $this->record(new WebhookSubscriptionDisabled($this->id->value()));
    }

    /**
     * Reversible, unlike delete() — disable() is meant for "pause deliveries while debugging an
     * integration", not a security action (contrast with ApiClient::revoke(), which is
     * deliberately one-way).
     */
    public function enable(): void
    {
        $this->status = WebhookSubscriptionStatus::ACTIVE;
        $this->touch();

        $this->record(new WebhookSubscriptionEnabled($this->id->value()));
    }

    public function delete(): void
    {
        $this->status = WebhookSubscriptionStatus::DELETED;
        $this->touch();

        $this->record(new WebhookSubscriptionDeleted($this->id->value()));
    }

    public function isActive(): bool
    {
        return WebhookSubscriptionStatus::ACTIVE === $this->status;
    }

    public function subscribesTo(string $eventName): bool
    {
        return $this->isActive() && \in_array($eventName, $this->eventNames, true);
    }

    /**
     * @return DomainEvent[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): WebhookSubscriptionId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function url(): WebhookUrl
    {
        return $this->url;
    }

    public function secret(): string
    {
        return $this->secret;
    }

    /**
     * @return list<string>
     */
    public function eventNames(): array
    {
        return $this->eventNames;
    }

    public function status(): WebhookSubscriptionStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
