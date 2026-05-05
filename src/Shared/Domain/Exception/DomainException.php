<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

abstract class DomainException extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    abstract public function errorCode(): string;
}
