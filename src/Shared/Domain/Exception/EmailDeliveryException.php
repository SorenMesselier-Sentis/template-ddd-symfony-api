<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class EmailDeliveryException extends DomainException
{
    public static function create(string $recipient, \Throwable $previous): self
    {
        return new self(
            message: sprintf('Failed to deliver email to "%s".', $recipient),
            previous: $previous,
        );
    }

    public function errorCode(): string
    {
        return 'email.delivery_failed';
    }
}
