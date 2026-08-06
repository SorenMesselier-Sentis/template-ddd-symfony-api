<?php

declare(strict_types=1);

namespace App\Tests\Integration\Project\Infrastructure;

use App\Project\Domain\ValueObject\OwnerId;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Infrastructure\Persistence\Doctrine\Repository\DoctrineProjectRepository;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Filter\Order;
use App\Shared\Domain\Filter\Pagination;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Unit\Project\Domain\Mother\ProjectMother;

final class DoctrineProjectRepositoryTest extends IntegrationTestCase
{
    private DoctrineProjectRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DoctrineProjectRepository($this->em);
    }

    public function testItSavesAndFindsAProject(): void
    {
        $entity = ProjectMother::create();
        $this->repository->save($entity);

        $found = $this->repository->findById($entity->id());

        $this->assertNotNull($found);
        $this->assertTrue($entity->id()->equals($found->id()));
        $this->assertTrue($entity->ownerId()->equals($found->ownerId()));
        $this->assertTrue($entity->name()->equals($found->name()));
    }

    public function testItReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->repository->findById(ProjectId::random()));
    }

    public function testFindByIdReturnsNullForADeletedProject(): void
    {
        $entity = ProjectMother::create();
        $entity->delete();
        $this->repository->save($entity);

        $this->assertNull($this->repository->findById($entity->id()));
        $this->assertNotNull($this->repository->findByIdIncludingDeleted($entity->id()));
    }

    public function testExistsByOwnerIdAndNameIsScopedPerOwner(): void
    {
        $ownerId = OwnerId::random();
        $otherOwnerId = OwnerId::random();
        $name = ProjectName::fromString('Shared project name');

        $this->repository->save(ProjectMother::create(ownerId: $ownerId, name: $name));

        $this->assertTrue($this->repository->existsByOwnerIdAndName($ownerId, $name));
        $this->assertFalse($this->repository->existsByOwnerIdAndName($otherOwnerId, $name));
    }

    public function testFindByOwnerIdAndFiltersOnlyReturnsThatOwnersActiveProjects(): void
    {
        $ownerId = OwnerId::random();
        $otherOwnerId = OwnerId::random();

        $owned = ProjectMother::create(ownerId: $ownerId);
        $deleted = ProjectMother::create(ownerId: $ownerId);
        $deleted->delete();
        $foreign = ProjectMother::create(ownerId: $otherOwnerId);

        foreach ([$owned, $deleted, $foreign] as $project) {
            $this->repository->save($project);
        }

        $filters = new Filters([], Order::default(), Pagination::fromRequest(1, 20));

        $found = $this->repository->findByOwnerIdAndFilters($ownerId, $filters);
        $foundIds = array_map(static fn ($p) => $p->id()->value(), $found);

        $this->assertContains($owned->id()->value(), $foundIds);
        $this->assertNotContains($deleted->id()->value(), $foundIds);
        $this->assertNotContains($foreign->id()->value(), $foundIds);
        $this->assertSame(\count($foundIds), $this->repository->countByOwnerIdAndFilters($ownerId, $filters));
    }
}
