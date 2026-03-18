<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

abstract class NotFoundException extends DomainException
{
    public function errorCode(): string
    {
        return 'not_found';
    }
}
