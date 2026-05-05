<?php

declare(strict_types=1);

namespace App\Shared\Domain\Filter;

final class Filter
{
    public function __construct(
        public readonly string $field,
        public readonly mixed $value,
        public readonly FilterOperator $operator,
    ) {
    }

    public static function equal(string $field, mixed $value): self
    {
        return new self($field, $value, FilterOperator::EQUAL);
    }

    public static function min(string $field, mixed $value): self
    {
        return new self($field, $value, FilterOperator::GREATER_THAN_OR_EQUAL);
    }

    public static function max(string $field, mixed $value): self
    {
        return new self($field, $value, FilterOperator::LESS_THAN_OR_EQUAL);
    }

    public static function in(string $field, mixed $value): self
    {
        return new self($field, $value, FilterOperator::IN);
    }
}
