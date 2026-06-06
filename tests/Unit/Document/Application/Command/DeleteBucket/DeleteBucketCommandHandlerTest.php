<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Application\Command\DeleteBucket;

use App\Document\Application\Command\DeleteBucket\DeleteBucketCommand;
use App\Document\Application\Command\DeleteBucket\DeleteBucketCommandHandler;
use App\Document\Domain\Exception\BucketNotEmptyException;
use App\Document\Domain\Exception\BucketNotFoundException;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\Storage\BucketManagerInterface;
use App\Tests\Unit\UnitTestCase;

final class DeleteBucketCommandHandlerTest extends UnitTestCase
{
    public function testItDeletesEmptyBucket(): void
    {
        $checker = $this->createStub(BucketExistenceCheckerInterface::class);
        $checker->method('exists')->willReturn(true);

        $repository = $this->createStub(DocumentRepositoryInterface::class);
        $repository->method('hasActiveDocumentsInBucket')->willReturn(false);

        $bucketManager = $this->createMock(BucketManagerInterface::class);
        $bucketManager->expects($this->once())->method('delete');

        $result = $this->handler($bucketManager, $checker, $repository)->__invoke(
            new DeleteBucketCommand(name: 'archive'),
        );

        $this->assertSame('archive', $result->name);
    }

    public function testItRejectsMissingBucket(): void
    {
        $checker = $this->createStub(BucketExistenceCheckerInterface::class);
        $checker->method('exists')->willReturn(false);

        $this->expectException(BucketNotFoundException::class);

        $this->handler(bucketChecker: $checker)->__invoke(
            new DeleteBucketCommand(name: 'missing'),
        );
    }

    public function testItRejectsNonEmptyBucket(): void
    {
        $checker = $this->createStub(BucketExistenceCheckerInterface::class);
        $checker->method('exists')->willReturn(true);

        $repository = $this->createStub(DocumentRepositoryInterface::class);
        $repository->method('hasActiveDocumentsInBucket')->willReturn(true);

        $bucketManager = $this->createMock(BucketManagerInterface::class);
        $bucketManager->expects($this->never())->method('delete');

        $this->expectException(BucketNotEmptyException::class);

        $this->handler($bucketManager, $checker, $repository)->__invoke(
            new DeleteBucketCommand(name: 'documents'),
        );
    }

    private function handler(
        ?BucketManagerInterface $bucketManager = null,
        ?BucketExistenceCheckerInterface $bucketChecker = null,
        ?DocumentRepositoryInterface $repository = null,
    ): DeleteBucketCommandHandler {
        return new DeleteBucketCommandHandler(
            bucketManager: $bucketManager ?? $this->createStub(BucketManagerInterface::class),
            bucketChecker: $bucketChecker ?? $this->createStub(BucketExistenceCheckerInterface::class),
            documentRepository: $repository ?? $this->createStub(DocumentRepositoryInterface::class),
        );
    }
}
