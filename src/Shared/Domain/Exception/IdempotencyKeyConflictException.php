<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class IdempotencyKeyConflictException extends DomainException
{
    public static function create(string $idempotencyKey): self
    {
        return new self(sprintf('Idempotency-Key "%s" was already used with a different request body.', $idempotencyKey));
    }

    public function errorCode(): string
    {
        return 'idempotency_key.conflict';
    }
}
