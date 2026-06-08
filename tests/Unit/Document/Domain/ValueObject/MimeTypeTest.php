<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Domain\ValueObject;

use App\Document\Domain\Exception\InvalidMimeTypeException;
use App\Document\Domain\ValueObject\MimeType;
use App\Tests\Unit\UnitTestCase;

final class MimeTypeTest extends UnitTestCase
{
    public function testItAcceptsValidMimeType(): void
    {
        $mimeType = MimeType::fromString('application/pdf');

        $this->assertSame('application/pdf', $mimeType->value());
    }

    public function testItRejectsInvalidMimeType(): void
    {
        $this->expectException(InvalidMimeTypeException::class);

        MimeType::fromString('invalid');
    }
}
