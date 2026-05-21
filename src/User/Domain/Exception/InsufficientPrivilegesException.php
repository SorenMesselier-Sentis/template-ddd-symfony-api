<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InsufficientPrivilegesException extends DomainException
{
    public static function create(): self
    {
        return new self('You do not have sufficient privileges to perform this action.');
    }

    public function errorCode(): string
    {
        return 'insufficient_privileges';
    }
}
