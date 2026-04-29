<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\Product\Domain\Mother\ProductMother;
use App\Product\Domain\Event\ProductCreated;

final class ProductTest extends UnitTestCase
{
    public function test_it_creates_a_product(): void
    {
        $entity = ProductMother::create();
        $events = $entity->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductCreated::class, $events[0]);
    }

    public function test_it_clears_domain_events_after_pull(): void
    {
        $entity = ProductMother::create();
        $entity->pullDomainEvents();

        $this->assertEmpty($entity->pullDomainEvents());
    }
}