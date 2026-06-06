<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Application\Query\ListBuckets;

use App\Document\Application\Query\ListBuckets\ListBucketsQuery;
use App\Document\Application\Query\ListBuckets\ListBucketsQueryHandler;
use App\Document\Domain\Storage\BucketManagerInterface;
use App\Document\Domain\ValueObject\BucketInfo;
use App\Document\Domain\ValueObject\BucketName;
use App\Tests\Unit\UnitTestCase;

final class ListBucketsQueryHandlerTest extends UnitTestCase
{
    public function testItListsBuckets(): void
    {
        $bucketManager = $this->createStub(BucketManagerInterface::class);
        $bucketManager->method('list')->willReturn([
            new BucketInfo(
                name: BucketName::fromString('documents'),
                createdAt: new \DateTimeImmutable('2026-06-06T10:00:00+00:00'),
            ),
            new BucketInfo(
                name: BucketName::fromString('archive'),
                createdAt: new \DateTimeImmutable('2026-06-06T11:00:00+00:00'),
            ),
        ]);

        $result = (new ListBucketsQueryHandler($bucketManager))->__invoke(new ListBucketsQuery());

        $this->assertCount(2, $result->buckets);
        $this->assertSame('documents', $result->buckets[0]['name']);
        $this->assertSame('2026-06-06T10:00:00+00:00', $result->buckets[0]['createdAt']);
    }
}
