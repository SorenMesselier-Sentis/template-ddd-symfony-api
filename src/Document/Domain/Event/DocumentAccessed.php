<?php

declare(strict_types=1);

namespace App\Document\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class DocumentAccessed extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public readonly string $requesterId,
    ) {
        parent::__construct($aggregateId);
    }

    public static function eventName(): string
    {
        return 'document.accessed';
    }
}
