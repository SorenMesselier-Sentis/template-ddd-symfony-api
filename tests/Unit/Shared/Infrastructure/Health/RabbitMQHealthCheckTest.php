<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Health;

use App\Shared\Infrastructure\Health\Check\RabbitMQHealthCheck;
use App\Tests\Unit\UnitTestCase;

final class RabbitMQHealthCheckTest extends UnitTestCase
{
    public function testNameIsRabbitMq(): void
    {
        $check = new RabbitMQHealthCheck(
            dsn: 'amqp://app:secret@rabbitmq:5672/%2f',
            connectionFactory: static fn (): object => new FakeAmqpConnection(connectResult: true),
        );

        $this->assertSame('rabbitmq', $check->name());
    }

    public function testCheckReturnsOkAndUsesOneSecondTimeouts(): void
    {
        $capturedConfig = [];
        $connection = new FakeAmqpConnection(connectResult: true);
        $check = new RabbitMQHealthCheck(
            dsn: 'amqp://app:secret@rabbitmq:5672/%2f',
            connectionFactory: static function (array $config) use (&$capturedConfig, $connection): object {
                $capturedConfig = $config;

                return $connection;
            },
        );

        $status = $check->check();

        $this->assertTrue($status->isOk());
        $this->assertSame('rabbitmq', $capturedConfig['host']);
        $this->assertSame('/', $capturedConfig['vhost']);
        $this->assertSame(1, $capturedConfig['connect_timeout']);
        $this->assertSame(1, $capturedConfig['read_timeout']);
        $this->assertSame(1, $capturedConfig['write_timeout']);
        $this->assertTrue($connection->isDisconnected);
    }

    public function testCheckReturnsErrorWhenConnectionFails(): void
    {
        $check = new RabbitMQHealthCheck(
            dsn: 'amqp://app:secret@rabbitmq:5672/%2f',
            connectionFactory: static fn (): object => new FakeAmqpConnection(connectResult: false),
        );

        $status = $check->check();

        $this->assertTrue($status->isError());
        $this->assertSame('Unable to connect to RabbitMQ', $status->detail());
    }

    public function testCheckReturnsErrorWhenConnectionThrows(): void
    {
        $check = new RabbitMQHealthCheck(
            dsn: 'amqp://app:secret@rabbitmq:5672/%2f',
            connectionFactory: static fn (): object => new FakeAmqpConnection(
                connectResult: false,
                exception: new \RuntimeException('Broker timeout'),
            ),
        );

        $status = $check->check();

        $this->assertTrue($status->isError());
        $this->assertSame('Broker timeout', $status->detail());
    }
}

final class FakeAmqpConnection
{
    public bool $isDisconnected = false;

    public function __construct(
        private readonly bool $connectResult,
        private readonly ?\Throwable $exception = null,
    ) {
    }

    public function connect(): bool
    {
        if (null !== $this->exception) {
            throw $this->exception;
        }

        return $this->connectResult;
    }

    public function disconnect(): void
    {
        $this->isDisconnected = true;
    }
}
