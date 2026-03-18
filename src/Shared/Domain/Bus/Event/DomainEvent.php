<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Event;

use Symfony\Component\Uid\Uuid;

abstract class DomainEvent
{
    private string $eventId;
    private string $occurredOn;

    public function __construct()
    {
        $this->eventId = Uuid::v4()->toRfc4122();
        $this->occurredOn = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
    }

    abstract public function eventName(): string;

    public function aggregateId(): string
    {
        return $this->aggregateId();
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function occurredOn(): string
    {
        return $this->occurredOn;
    }
}
