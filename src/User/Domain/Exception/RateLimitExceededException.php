<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class RateLimitExceededException extends DomainException
{
    public static function create(): self
    {
        return new self('Too many requests. Please try again later.');
    }

    public function errorCode(): string
    {
        return 'rate_limit.exceeded';
    }
}
