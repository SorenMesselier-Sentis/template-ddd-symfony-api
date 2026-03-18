<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

abstract class AlreadyExistsException extends DomainException
{
    public function errorCode(): string
    {
        return 'already_exists';
    }
}
