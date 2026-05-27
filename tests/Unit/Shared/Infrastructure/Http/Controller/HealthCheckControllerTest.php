<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Controller;

use App\Shared\Domain\Health\HealthCheckInterface;
use App\Shared\Domain\Health\HealthCheckStatus;
use App\Shared\Infrastructure\Health\HealthCheckRegistry;
use App\Shared\Infrastructure\Http\Controller\HealthCheckController;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Serializer\SerializerInterface;

final class HealthCheckControllerTest extends UnitTestCase
{
    public function testItReturnsReadinessPayloadWithLegacyAndDetailedChecks(): void
    {
        $registry = new HealthCheckRegistry([
            $this->healthCheck('api', fn (): HealthCheckStatus => HealthCheckStatus::ok()),
            $this->healthCheck('database', fn (): HealthCheckStatus => HealthCheckStatus::ok()),
        ]);
        $controller = new HealthCheckController($registry, $this->apiResponse());

        $response = $controller();

        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('ok', $payload['data']['status']);
        $this->assertSame('ok', $payload['data']['checks']['database']);
        $this->assertIsInt($payload['data']['checks_details']['database']['duration_ms']);
        $this->assertGreaterThanOrEqual(0, $payload['data']['checks_details']['database']['duration_ms']);
    }

    public function testItReturns503WhenAReadinessCheckFails(): void
    {
        $registry = new HealthCheckRegistry([
            $this->healthCheck('api', fn (): HealthCheckStatus => HealthCheckStatus::ok()),
            $this->healthCheck('database', fn (): HealthCheckStatus => HealthCheckStatus::error('Connection refused')),
        ]);
        $controller = new HealthCheckController($registry, $this->apiResponse());

        $response = $controller();

        $this->assertSame(503, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('error', $payload['data']['status']);
    }

    private function apiResponse(): ApiResponse
    {
        $serializer = $this->createStub(SerializerInterface::class);
        $serializer
            ->method('serialize')
            ->willReturnCallback(static fn (mixed $data): string => json_encode($data, JSON_THROW_ON_ERROR));

        return new ApiResponse($serializer);
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
