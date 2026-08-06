<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Query;

use App\Shared\Domain\FeatureFlag\FeatureFlag;
use App\Shared\Domain\FeatureFlag\FeatureFlagRepositoryInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Query\GetFeatureFlags\GetFeatureFlagsQuery;
use App\User\Application\Query\GetFeatureFlags\GetFeatureFlagsQueryHandler;

final class GetFeatureFlagsQueryHandlerTest extends UnitTestCase
{
    public function testItReturnsAllFlags(): void
    {
        $flag = new FeatureFlag('cursor_pagination', true, 'desc', new \DateTimeImmutable('2026-01-01 00:00:00'));

        $repository = $this->createMock(FeatureFlagRepositoryInterface::class);
        $repository->expects($this->once())->method('findAll')->willReturn([$flag]);

        $response = (new GetFeatureFlagsQueryHandler($repository))(new GetFeatureFlagsQuery());

        $this->assertCount(1, $response->flags);
        $this->assertSame('cursor_pagination', $response->flags[0]->key);
        $this->assertTrue($response->flags[0]->enabled);
        $this->assertSame('desc', $response->flags[0]->description);
    }
}
