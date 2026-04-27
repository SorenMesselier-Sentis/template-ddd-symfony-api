<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class InvalidFilterException extends InvalidArgumentException
{
    public static function unknownField(string $field): self
    {
        return new self(sprintf('Unknown filter field "%s".', $field));
    }

    public static function unsupportedOperator(string $field): self
    {
        return new self(sprintf('Unsupported operator for field "%s".', $field));
    }

    public static function invalidSortField(string $field): self
    {
        return new self(sprintf('Sorting by "%s" is not allowed.', $field));
    }

    public static function invalidPagination(string $name): self
    {
        return new self(sprintf('Invalid pagination value for "%s".', $name));
    }

    public function errorCode(): string
    {
        return 'invalid_filter';
    }
}
