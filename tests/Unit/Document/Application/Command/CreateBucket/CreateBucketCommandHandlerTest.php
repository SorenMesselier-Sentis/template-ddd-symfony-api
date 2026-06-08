<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Application\Command\CreateBucket;

use App\Document\Application\Command\CreateBucket\CreateBucketCommand;
use App\Document\Application\Command\CreateBucket\CreateBucketCommandHandler;
use App\Document\Domain\Exception\BucketAlreadyExistsException;
use App\Document\Domain\Exception\InvalidBucketNameException;
use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\Storage\BucketManagerInterface;
use App\Document\Domain\ValueObject\BucketInfo;
use App\Document\Domain\ValueObject\BucketName;
use App\Tests\Unit\UnitTestCase;

final class CreateBucketCommandHandlerTest extends UnitTestCase
{
    public function testItCreatesBucket(): void
    {
        $bucketManager = $this->createMock(BucketManagerInterface::class);
        $bucketManager->expects($this->once())->method('create');
        $bucketManager->method('list')->willReturn([
            new BucketInfo(
                name: BucketName::fromString('reports'),
                createdAt: new \DateTimeImmutable('2026-06-06T12:00:00+00:00'),
            ),
        ]);

        $checker = $this->createStub(BucketExistenceCheckerInterface::class);
        $checker->method('exists')->willReturn(false);

        $result = $this->handler($bucketManager, $checker)->__invoke(
            new CreateBucketCommand(name: 'reports'),
        );

        $this->assertSame('reports', $result->name);
        $this->assertSame('2026-06-06T12:00:00+00:00', $result->createdAt);
    }

    public function testItRejectsExistingBucket(): void
    {
        $checker = $this->createStub(BucketExistenceCheckerInterface::class);
        $checker->method('exists')->willReturn(true);

        $this->expectException(BucketAlreadyExistsException::class);

        $this->handler(bucketChecker: $checker)->__invoke(
            new CreateBucketCommand(name: 'documents'),
        );
    }

    public function testItRejectsInvalidBucketName(): void
    {
        $this->expectException(InvalidBucketNameException::class);

        $this->handler()->__invoke(new CreateBucketCommand(name: '-invalid'));
    }

    private function handler(
        ?BucketManagerInterface $bucketManager = null,
        ?BucketExistenceCheckerInterface $bucketChecker = null,
    ): CreateBucketCommandHandler {
        return new CreateBucketCommandHandler(
            bucketManager: $bucketManager ?? $this->createStub(BucketManagerInterface::class),
            bucketChecker: $bucketChecker ?? $this->createStub(BucketExistenceCheckerInterface::class),
        );
    }
}
