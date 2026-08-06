<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\FeatureFlag\FeatureFlag;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineFeatureFlagRepository;
use App\Tests\Integration\IntegrationTestCase;

final class DoctrineFeatureFlagRepositoryTest extends IntegrationTestCase
{
    private DoctrineFeatureFlagRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DoctrineFeatureFlagRepository($this->em->getConnection());
    }

    public function testFindByKeyReturnsNullWhenFlagDoesNotExist(): void
    {
        $this->assertNull($this->repository->findByKey('does_not_exist_'.uniqid()));
    }

    public function testIsEnabledReturnsFalseForUnknownFlag(): void
    {
        $this->assertFalse($this->repository->isEnabled('does_not_exist_'.uniqid()));
    }

    public function testSaveCreatesThenUpdatesAFlag(): void
    {
        $key = 'test_flag_'.uniqid();

        $this->repository->save(new FeatureFlag($key, true, 'first version', new \DateTimeImmutable('2026-01-01 00:00:00')));

        $created = $this->repository->findByKey($key);
        $this->assertNotNull($created);
        $this->assertTrue($created->enabled);
        $this->assertSame('first version', $created->description);
        $this->assertTrue($this->repository->isEnabled($key));

        $this->repository->save(new FeatureFlag($key, false, 'second version', new \DateTimeImmutable('2026-01-02 00:00:00')));

        $updated = $this->repository->findByKey($key);
        $this->assertNotNull($updated);
        $this->assertFalse($updated->enabled);
        $this->assertSame('second version', $updated->description);
        $this->assertFalse($this->repository->isEnabled($key));
    }

    public function testSaveAcceptsANullDescription(): void
    {
        $key = 'test_flag_'.uniqid();

        $this->repository->save(new FeatureFlag($key, true, null, new \DateTimeImmutable()));

        $flag = $this->repository->findByKey($key);
        $this->assertNotNull($flag);
        $this->assertNull($flag->description);
    }

    public function testFindAllIncludesTheSeededCursorPaginationFlag(): void
    {
        $flags = $this->repository->findAll();
        $keys = array_map(static fn (FeatureFlag $flag): string => $flag->key, $flags);

        $this->assertContains('cursor_pagination', $keys);
    }
}
