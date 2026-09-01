<?php

declare(strict_types=1);

namespace App\ApiClient\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class ApiClientDeleted extends DomainEvent
{
    public function __construct(string $aggregateId)
    {
        parent::__construct($aggregateId);
    }

    public static function eventName(): string
    {
        return 'api_client.deleted';
    }
}
