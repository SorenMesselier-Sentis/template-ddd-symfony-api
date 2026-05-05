<?php

declare(strict_types=1);

namespace App\Shared\Domain\Filter;

final class Order
{
    public function __construct(
        public readonly string $field,
        public readonly OrderType $type,
    ) {
    }

    public static function fromString(string $sort): self
    {
        if (str_starts_with($sort, '-')) {
            return new self(ltrim($sort, '-'), OrderType::DESC);
        }

        return new self($sort, OrderType::ASC);
    }

    public static function default(): self
    {
        return new self('createdAt', OrderType::DESC);
    }
}
