<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging\RabbitMQ;

final class DeadLetterMessage
{
    public function __construct(
        public readonly mixed $originalMessage,
    ) {}
}
