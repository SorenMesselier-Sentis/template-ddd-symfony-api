<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\Mother;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\ValueObject\ProductId;

final class ProductMother
{
    public static function create(?ProductId $id = null): Product
    {
        return Product::create(id: $id ?? ProductIdMother::random());
    }
}