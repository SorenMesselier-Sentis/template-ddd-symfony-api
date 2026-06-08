<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Monitoring\Tracing;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Monitoring\Tracing\NoOpTracer;
use App\Shared\Infrastructure\Monitoring\Tracing\TracerMiddleware;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\LoginUser\LoginUserCommand;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

final class TracerMiddlewareTest extends UnitTestCase
{
    public function testSpanIsEndedAfterSuccessfulHandle(): void
    {
        $middleware = new TracerMiddleware(new NoOpTracer());
        $envelope = new Envelope(new LoginUserCommand(Email::fromString('a@b.com'), 'secret'), [new BusNameStamp('command.bus')]);

        $result = $middleware->handle($envelope, $this->terminatingStack($envelope));
        $this->assertSame($envelope, $result);
    }

    public function testExceptionIsRethrownAfterRecording(): void
    {
        $this->expectException(\RuntimeException::class);

        $middleware = new TracerMiddleware(new NoOpTracer());
        $envelope = new Envelope(new LoginUserCommand(Email::fromString('a@b.com'), 'secret'));

        $middleware->handle($envelope, $this->failingStack());
    }

    private function terminatingStack(Envelope $envelope): StackInterface
    {
        return new class($envelope) implements StackInterface {
            public function __construct(private readonly Envelope $envelope)
            {
            }

            public function next(): MiddlewareInterface
            {
                return new class($this->envelope) implements MiddlewareInterface {
                    public function __construct(private readonly Envelope $envelope)
                    {
                    }

                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        return $this->envelope;
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
