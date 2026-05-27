<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Health;

use App\Shared\Infrastructure\Health\Check\DatabaseHealthCheck;
use App\Tests\Unit\UnitTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;

final class DatabaseHealthCheckTest extends UnitTestCase
{
    public function testNameIsDatabase(): void
    {
        $connection = $this->createStub(Connection::class);
        $check = new DatabaseHealthCheck($connection);

        $this->assertSame('database', $check->name());
    }

    public function testCheckReturnsOkWhenDatabaseIsReachable(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('getDummySelectSQL')->willReturn('SELECT 1');

        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createStub(Result::class));

        $status = (new DatabaseHealthCheck($connection))->check();

        $this->assertTrue($status->isOk());
        $this->assertSame('ok', $status->state()->value);
    }

    public function testCheckReturnsErrorWhenDatabaseFails(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('getDummySelectSQL')->willReturn('SELECT 1');

        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT 1')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $status = (new DatabaseHealthCheck($connection))->check();

        $this->assertTrue($status->isError());
        $this->assertSame('error', $status->state()->value);
        $this->assertSame('Connection refused', $status->detail());
    }
}
