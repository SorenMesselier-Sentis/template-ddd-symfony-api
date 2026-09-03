<?php

declare(strict_types=1);

namespace App\Tests\Integration\ApiClient\Infrastructure;

use App\ApiClient\Domain\ValueObject\ApiClientId;
use App\ApiClient\Infrastructure\Persistence\Doctrine\Repository\DoctrineApiClientRepository;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Filter\Order;
use App\Shared\Domain\Filter\Pagination;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Unit\ApiClient\Domain\Mother\ApiClientMother;

final class DoctrineApiClientRepositoryTest extends IntegrationTestCase
{
    private DoctrineApiClientRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DoctrineApiClientRepository($this->em);
    }

    public function testItSavesAndFindsAnApiClient(): void
    {
        $entity = ApiClientMother::create(scopes: ['documents:write', 'documents:read']);
        $this->repository->save($entity);

        $found = $this->repository->findById($entity->id());

        $this->assertNotNull($found);
        $this->assertTrue($entity->id()->equals($found->id()));
        $this->assertSame($entity->name(), $found->name());
        $this->assertSame(['documents:write', 'documents:read'], $found->scopes());
        $this->assertTrue($found->secretHash()->verify('test-secret'));
    }

    public function testItReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->repository->findById(ApiClientId::random()));
    }

    public function testFindByIdReturnsNullForADeletedClient(): void
    {
        $entity = ApiClientMother::create();
        $entity->delete();
        $this->repository->save($entity);

        $this->assertNull($this->repository->findById($entity->id()));
        $this->assertNotNull($this->repository->findByIdIncludingDeleted($entity->id()));
    }

    public function testFindByFiltersExcludesDeletedClients(): void
    {
        $active = ApiClientMother::create(name: 'Active client '.uniqid());
        $deleted = ApiClientMother::create(name: 'Deleted client '.uniqid());
        $deleted->delete();

        $this->repository->save($active);
        $this->repository->save($deleted);

        $filters = new Filters([], Order::default(), Pagination::fromRequest(1, 100));
        $found = $this->repository->findByFilters($filters);
        $foundIds = array_map(static fn ($c) => $c->id()->value(), $found);

        $this->assertContains($active->id()->value(), $foundIds);
        $this->assertNotContains($deleted->id()->value(), $foundIds);
    }
}
