<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

abstract class InvalidArgumentException extends DomainException
{
    public function errorCode(): string
    {
        return 'invalid_argument';
    }
}
