<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Health;

use App\Shared\Infrastructure\Health\HealthChecker;
use App\Tests\Unit\UnitTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;

final class HealthCheckerTest extends UnitTestCase
{
    /** @var Connection&MockObject */
    private Connection $connection;

    private HealthChecker $healthChecker;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->healthChecker = new HealthChecker($this->connection);
    }

    public function testItReturnsOkWhenDatabaseIsReachable(): void
    {
        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('getDummySelectSQL')->willReturn('SELECT 1');

        $this->connection
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createStub(Result::class));

        $result = $this->healthChecker->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame(200, $result->httpStatusCode());
        $this->assertSame('ok', $result->status);
        $this->assertSame(['api' => 'ok', 'database' => 'ok'], $result->checks);
    }

    public function testItReturnsErrorWhenDatabaseIsUnreachable(): void
    {
        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('getDummySelectSQL')->willReturn('SELECT 1');

        $this->connection
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $result = $this->healthChecker->check();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(503, $result->httpStatusCode());
        $this->assertSame('error', $result->status);
        $this->assertSame('error', $result->checks['database']);
    }
}
