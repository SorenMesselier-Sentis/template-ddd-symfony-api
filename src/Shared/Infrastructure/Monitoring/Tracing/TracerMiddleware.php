<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Tracing;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

final class TracerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly TracerInterface $tracer,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $bus = $envelope->last(BusNameStamp::class)?->getBusName() ?? 'unknown';
        $name = (new \ReflectionClass($envelope->getMessage()))->getShortName();
        $span = $this->tracer->startSpan($name, ['messaging.system' => $bus]);

        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $exception) {
            try {
                $span->recordException($exception);
            } catch (\Throwable) {
            }

            throw $exception;
        } finally {
            $span->end();
        }
    }
}
