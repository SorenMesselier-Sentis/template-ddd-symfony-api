<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class UserRegistered extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
    ) {
        parent::__construct($aggregateId);
    }

    public static function eventName(): string
    {
        return 'user.registered';
    }
}
