<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Application\Query\CheckBucketExists;

use App\Document\Application\Query\CheckBucketExists\CheckBucketExistsQuery;
use App\Document\Application\Query\CheckBucketExists\CheckBucketExistsQueryHandler;
use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Tests\Unit\UnitTestCase;

final class CheckBucketExistsQueryHandlerTest extends UnitTestCase
{
    public function testItReturnsTrueWhenBucketExists(): void
    {
        $checker = $this->createStub(BucketExistenceCheckerInterface::class);
        $checker->method('exists')->willReturnCallback(
            static fn (BucketName $bucket): bool => 'documents' === $bucket->value(),
        );

        $result = (new CheckBucketExistsQueryHandler($checker))->__invoke(
            new CheckBucketExistsQuery(name: 'documents'),
        );

        $this->assertSame('documents', $result->name);
        $this->assertTrue($result->exists);
    }

    public function testItReturnsFalseWhenBucketDoesNotExist(): void
    {
        $checker = $this->createStub(BucketExistenceCheckerInterface::class);
        $checker->method('exists')->willReturn(false);

        $result = (new CheckBucketExistsQueryHandler($checker))->__invoke(
            new CheckBucketExistsQuery(name: 'missing'),
        );

        $this->assertFalse($result->exists);
    }
}
