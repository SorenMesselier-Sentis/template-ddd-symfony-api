<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Filter;

use App\Shared\Domain\Exception\InvalidFilterException;
use App\Shared\Domain\Filter\Cursor;
use App\Tests\Unit\UnitTestCase;

final class CursorTest extends UnitTestCase
{
    public function testEncodeThenDecodeRoundTrips(): void
    {
        $cursor = new Cursor(new \DateTimeImmutable('2026-01-15 10:30:00'), 'a1b2c3d4-0000-0000-0000-000000000000');

        $decoded = Cursor::decode($cursor->encode());

        $this->assertSame('2026-01-15 10:30:00', $decoded->createdAt->format('Y-m-d H:i:s'));
        $this->assertSame('a1b2c3d4-0000-0000-0000-000000000000', $decoded->id);
    }

    public function testDecodeRejectsNonBase64Token(): void
    {
        $this->expectException(InvalidFilterException::class);

        Cursor::decode('not valid base64!!!');
    }

    public function testDecodeRejectsMalformedJson(): void
    {
        $this->expectException(InvalidFilterException::class);

        Cursor::decode(base64_encode('not json'));
    }

    public function testDecodeRejectsMissingFields(): void
    {
        $this->expectException(InvalidFilterException::class);

        Cursor::decode(base64_encode(json_encode(['id' => 'abc'])));
    }

    public function testDecodeRejectsInvalidDate(): void
    {
        $this->expectException(InvalidFilterException::class);

        Cursor::decode(base64_encode(json_encode(['created_at' => 'not-a-date', 'id' => 'abc'])));
    }
}
