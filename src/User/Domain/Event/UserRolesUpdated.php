<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class UserRolesUpdated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public readonly array $roles,
    ) {
        parent::__construct($aggregateId);
    }

    public static function eventName(): string
    {
        return 'user.roles_updated';
    }
}
