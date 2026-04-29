<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\Mother;

use App\Product\Domain\ValueObject\ProductId;

final class ProductIdMother
{
    public static function random(): ProductId
    {
        return ProductId::random();
    }

    public static function create(string $value): ProductId
    {
        return ProductId::fromString($value);
    }
}