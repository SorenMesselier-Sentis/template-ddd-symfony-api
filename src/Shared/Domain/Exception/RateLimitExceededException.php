<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class RateLimitExceededException extends DomainException
{
    private function __construct(
        string $message,
        public readonly ?int $retryAfterSeconds,
    ) {
        parent::__construct($message);
    }

    public static function create(?int $retryAfterSeconds = null): self
    {
        return new self('Too many requests. Please try again later.', $retryAfterSeconds);
    }

    public function errorCode(): string
    {
        return 'rate_limit.exceeded';
    }
}
