<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Health;

use App\Shared\Domain\Health\HealthCheckInterface;
use App\Shared\Domain\Health\HealthCheckStatus;
use App\Shared\Infrastructure\Health\HealthCheckRegistry;
use App\Tests\Unit\UnitTestCase;

final class HealthCheckerTest extends UnitTestCase
{
    public function testItBuildsLegacyAndDetailedChecks(): void
    {
        $checks = [
            $this->healthCheck('api', fn (): HealthCheckStatus => HealthCheckStatus::ok()),
            $this->healthCheck('database', fn (): HealthCheckStatus => HealthCheckStatus::ok()),
        ];

        $result = (new HealthCheckRegistry($checks))->run();

        $this->assertTrue($result->isHealthy());
        $this->assertSame(200, $result->httpStatusCode());
        $this->assertSame('ok', $result->status->state()->value);
        $this->assertSame(['api' => 'ok', 'database' => 'ok'], $result->checks);
        $this->assertSame('ok', $result->checksDetails['database']->status);
        $this->assertIsInt($result->checksDetails['database']->durationMs);
    }

    public function testItReturnsErrorAnd503WhenOneCheckFails(): void
    {
        $checks = [
            $this->healthCheck('api', fn (): HealthCheckStatus => HealthCheckStatus::ok()),
            $this->healthCheck('database', fn (): HealthCheckStatus => HealthCheckStatus::error('Connection refused')),
        ];

        $result = (new HealthCheckRegistry($checks))->run();

        $this->assertFalse($result->isHealthy());
        $this->assertSame(503, $result->httpStatusCode());
        $this->assertSame('error', $result->status->state()->value);
        $this->assertSame('error', $result->checks['database']);
        $this->assertSame('Connection refused', $result->checksDetails['database']->detail);
    }

    public function testItKeepsSequentialExecutionOrder(): void
    {
        $executedChecks = [];
        $checks = [
            $this->healthCheck('first', function () use (&$executedChecks): HealthCheckStatus {
                $executedChecks[] = 'first';

                return HealthCheckStatus::ok();
            }),
            $this->healthCheck('second', function () use (&$executedChecks): HealthCheckStatus {
                $executedChecks[] = 'second';

                return HealthCheckStatus::ok();
            }),
        ];

        (new HealthCheckRegistry($checks))->run();

        $this->assertSame(['first', 'second'], $executedChecks);
    }

    public function testItHandlesThrownExceptionsAsErrorStatus(): void
    {
        $checks = [
            $this->healthCheck('database', static function (): HealthCheckStatus {
                throw new \RuntimeException('DB down');
            }),
        ];

        $result = (new HealthCheckRegistry($checks))->run();

        $this->assertSame('error', $result->checks['database']);
        $this->assertSame('DB down', $result->checksDetails['database']->detail);
    }

    public function testItTruncatesErrorDetailTo200Characters(): void
    {
        $longMessage = str_repeat('x', 250);
        $checks = [
            $this->healthCheck('rabbitmq', static fn (): HealthCheckStatus => HealthCheckStatus::error($longMessage)),
        ];

        $result = (new HealthCheckRegistry($checks))->run();

        $this->assertSame(200, mb_strlen((string) $result->checksDetails['rabbitmq']->detail));
    }

    /** @param callable():HealthCheckStatus $callback */
    private function healthCheck(string $name, callable $callback): HealthCheckInterface
    {
        return new class($name, $callback) implements HealthCheckInterface {
            public function __construct(
                private readonly string $name,
                private readonly mixed $callback,
            ) {
            }

            public function name(): string
            {
                return $this->name;
            }

            public function check(): HealthCheckStatus
            {
                return ($this->callback)();
            }
        };
    }
}
