<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class PasswordResetRequested extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $token,
    ) {
        parent::__construct($aggregateId);
    }

    public static function eventName(): string
    {
        return 'user.password_reset_requested';
    }
}
