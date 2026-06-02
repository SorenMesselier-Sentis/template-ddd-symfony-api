<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class ValidationException extends DomainException
{
    /**
     * @param list<ValidationError> $errors
     */
    public function __construct(
        private readonly array $errors,
    ) {
        parent::__construct('Validation failed.');
    }

    /**
     * @return list<ValidationError>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function errorCode(): string
    {
        return 'validation_error';
    }
}
