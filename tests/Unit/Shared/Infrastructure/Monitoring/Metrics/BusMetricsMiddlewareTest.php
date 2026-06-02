<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Monitoring\Metrics;

use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Monitoring\Metrics\BusMetricsMiddleware;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

final class BusMetricsMiddlewareTest extends UnitTestCase
{
    public function testItTracksReceivedAndHandledMessages(): void
    {
        $metrics = new MetricsCollectorSpy();
        $middleware = new BusMetricsMiddleware($metrics);
        $envelope = new Envelope(new DummyMessage(), [new BusNameStamp('command.bus')]);

        $result = $middleware->handle($envelope, $this->terminatingStack($envelope));

        $this->assertSame($envelope, $result);
        $this->assertCount(2, $metrics->counterCalls);
        $this->assertSame([
            'name' => 'messenger_messages_total',
            'labels' => ['bus' => 'command', 'message_class' => 'DummyMessage', 'status' => 'received'],
            'value' => 1.0,
        ], $metrics->counterCalls[0]);
        $this->assertSame([
            'name' => 'messenger_messages_total',
            'labels' => ['bus' => 'command', 'message_class' => 'DummyMessage', 'status' => 'handled'],
            'value' => 1.0,
        ], $metrics->counterCalls[1]);
        $this->assertCount(1, $metrics->histogramCalls);
        $this->assertSame('messenger_handler_duration_seconds', $metrics->histogramCalls[0]['name']);
        $this->assertGreaterThanOrEqual(0.0, $metrics->histogramCalls[0]['value']);
        $this->assertSame(
            ['bus' => 'command', 'message_class' => 'DummyMessage'],
            $metrics->histogramCalls[0]['labels'],
        );
    }

    public function testItTracksFailedMessagesAndRethrows(): void
    {
        $metrics = new MetricsCollectorSpy();
        $middleware = new BusMetricsMiddleware($metrics);
        $envelope = new Envelope(new DummyMessage(), [new BusNameStamp('event.bus')]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('handler failed');

        try {
            $middleware->handle($envelope, $this->failingStack());
        } finally {
            $this->assertCount(2, $metrics->counterCalls);
            $this->assertSame('received', $metrics->counterCalls[0]['labels']['status']);
            $this->assertSame('failed', $metrics->counterCalls[1]['labels']['status']);
            $this->assertSame('event', $metrics->counterCalls[0]['labels']['bus']);
            $this->assertSame('event', $metrics->counterCalls[1]['labels']['bus']);
            $this->assertCount(1, $metrics->histogramCalls);
        }
    }

    public function testItThrowsWhenBusStampIsMissing(): void
    {
        $middleware = new BusMetricsMiddleware(new MetricsCollectorSpy());
        $envelope = new Envelope(new DummyMessage());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported Messenger bus');

        $middleware->handle($envelope, $this->terminatingStack($envelope));
    }

    private function terminatingStack(Envelope $resultEnvelope): StackInterface
    {
        return new class($resultEnvelope) implements StackInterface {
            public function __construct(
                private readonly Envelope $resultEnvelope,
            ) {
            }

            public function next(): MiddlewareInterface
            {
                return new class($this->resultEnvelope) implements MiddlewareInterface {
                    public function __construct(
                        private readonly Envelope $resultEnvelope,
                    ) {
                    }

                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        return $this->resultEnvelope;
                    }
                };
            }
        };
    }

    private function failingStack(): StackInterface
    {
        return new class implements StackInterface {
            public function next(): MiddlewareInterface
            {
                return new class implements MiddlewareInterface {
                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        throw new \RuntimeException('handler failed');
                    }
                };
            }
        };
    }
}

final class DummyMessage
{
}

final class MetricsCollectorSpy implements MetricsCollectorInterface
{
    /** @var list<array{name: string, labels: array<string, string|int|float|bool>, value: float}> */
    public array $counterCalls = [];

    /** @var list<array{name: string, labels: array<string, string|int|float|bool>, value: float, buckets: list<float>|null}> */
    public array $histogramCalls = [];

    /** @var list<array{name: string, labels: array<string, string|int|float|bool>, value: float}> */
    public array $gaugeCalls = [];

    public function incrementCounter(string $name, array $labels = [], float $value = 1.0): void
    {
        $this->counterCalls[] = ['name' => $name, 'labels' => $labels, 'value' => $value];
    }

    public function observeHistogram(string $name, float $value, array $labels = [], ?array $buckets = null): void
    {
        $this->histogramCalls[] = ['name' => $name, 'labels' => $labels, 'value' => $value, 'buckets' => $buckets];
    }

    public function setGauge(string $name, float $value, array $labels = []): void
    {
        $this->gaugeCalls[] = ['name' => $name, 'labels' => $labels, 'value' => $value];
    }
}
