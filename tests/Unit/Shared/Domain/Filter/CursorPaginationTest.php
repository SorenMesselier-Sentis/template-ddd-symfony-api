<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Filter;

use App\Shared\Domain\Filter\Cursor;
use App\Shared\Domain\Filter\CursorPagination;
use App\Tests\Unit\UnitTestCase;

final class CursorPaginationTest extends UnitTestCase
{
    public function testFromRequestWithoutCursorTokenHasNoAfter(): void
    {
        $pagination = CursorPagination::fromRequest(null, 20);

        $this->assertNull($pagination->after);
        $this->assertSame(20, $pagination->limit);
    }

    public function testFromRequestWithEmptyCursorTokenHasNoAfter(): void
    {
        $pagination = CursorPagination::fromRequest('', 20);

        $this->assertNull($pagination->after);
    }

    public function testFromRequestDecodesCursorToken(): void
    {
        $cursor = new Cursor(new \DateTimeImmutable('2026-01-15 10:30:00'), 'a1b2c3d4-0000-0000-0000-000000000000');

        $pagination = CursorPagination::fromRequest($cursor->encode(), 20);

        $this->assertNotNull($pagination->after);
        $this->assertSame('a1b2c3d4-0000-0000-0000-000000000000', $pagination->after->id);
    }

    public function testLimitIsClampedBetweenOneAndOneHundred(): void
    {
        $this->assertSame(1, CursorPagination::fromRequest(null, 0)->limit);
        $this->assertSame(1, CursorPagination::fromRequest(null, -5)->limit);
        $this->assertSame(100, CursorPagination::fromRequest(null, 500)->limit);
    }
}
