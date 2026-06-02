<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Metrics;

use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

final class BusMetricsMiddleware implements MiddlewareInterface
{
    private const string MESSAGES_TOTAL = 'messenger_messages_total';
    private const string HANDLER_DURATION_SECONDS = 'messenger_handler_duration_seconds';

    public function __construct(
        private readonly MetricsCollectorInterface $metrics,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $bus = $this->resolveBusLabel($envelope);
        $messageClass = (new \ReflectionClass($envelope->getMessage()))->getShortName();

        $this->metrics->incrementCounter(self::MESSAGES_TOTAL, [
            'bus' => $bus,
            'message_class' => $messageClass,
            'status' => 'received',
        ]);

        $startedAt = microtime(true);

        try {
            $result = $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $exception) {
            $this->metrics->incrementCounter(self::MESSAGES_TOTAL, [
                'bus' => $bus,
                'message_class' => $messageClass,
                'status' => 'failed',
            ]);
            $this->metrics->observeHistogram(
                self::HANDLER_DURATION_SECONDS,
                microtime(true) - $startedAt,
                [
                    'bus' => $bus,
                    'message_class' => $messageClass,
                ],
            );

            throw $exception;
        }

        $this->metrics->incrementCounter(self::MESSAGES_TOTAL, [
            'bus' => $bus,
            'message_class' => $messageClass,
            'status' => 'handled',
        ]);
        $this->metrics->observeHistogram(
            self::HANDLER_DURATION_SECONDS,
            microtime(true) - $startedAt,
            [
                'bus' => $bus,
                'message_class' => $messageClass,
            ],
        );

        return $result;
    }

    private function resolveBusLabel(Envelope $envelope): string
    {
        $busName = $envelope->last(BusNameStamp::class)?->getBusName();

        return match ($busName) {
            'command.bus' => 'command',
            'query.bus' => 'query',
            'event.bus' => 'event',
            default => throw new \LogicException(sprintf('Unsupported Messenger bus "%s" for metrics middleware.', (string) $busName)),
        };
    }
}
