<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

abstract class UnauthorizedException extends DomainException
{
    public function errorCode(): string
    {
        return 'unauthorized';
    }
}
