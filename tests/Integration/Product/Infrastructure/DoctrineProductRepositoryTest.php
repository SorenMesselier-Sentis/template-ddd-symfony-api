<?php

declare(strict_types=1);

namespace App\Tests\Integration\Product\Infrastructure;

use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Unit\Product\Domain\Mother\ProductMother;
use App\Product\Domain\ValueObject\ProductId;
use App\Product\Infrastructure\Persistence\Doctrine\Repository\DoctrineProductRepository;

final class DoctrineProductRepositoryTest extends IntegrationTestCase
{
    private DoctrineProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = static::getContainer()->get(DoctrineProductRepository::class);
    }

    public function test_it_saves_and_finds_a_product(): void
    {
        $entity = ProductMother::create();
        $this->repository->save($entity);

        $found = $this->repository->findById($entity->id());

        $this->assertNotNull($found);
        $this->assertTrue($entity->id()->equals($found->id()));
    }

    public function test_it_returns_null_when_not_found(): void
    {
        $this->assertNull($this->repository->findById(ProductId::random()));
    }
}