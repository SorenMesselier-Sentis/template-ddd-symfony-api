<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Domain\ValueObject;

use App\Document\Domain\Exception\InvalidBucketNameException;
use App\Document\Domain\ValueObject\BucketName;
use App\Tests\Unit\UnitTestCase;

final class BucketNameTest extends UnitTestCase
{
    public function testItAcceptsValidBucketName(): void
    {
        $bucket = BucketName::fromString('documents');

        $this->assertSame('documents', $bucket->value());
    }

    public function testItRejectsInvalidBucketName(): void
    {
        $this->expectException(InvalidBucketNameException::class);

        BucketName::fromString('-invalid');
    }
}
