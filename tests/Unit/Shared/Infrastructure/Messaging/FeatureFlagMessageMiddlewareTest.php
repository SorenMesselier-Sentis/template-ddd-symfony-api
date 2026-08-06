<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messaging;

use App\Shared\Domain\Exception\FeatureDisabledException;
use App\Shared\Domain\FeatureFlag\FeatureFlagRepositoryInterface;
use App\Shared\Domain\Filter\CursorPagination;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Filter\Order;
use App\Shared\Domain\Filter\Pagination;
use App\Shared\Infrastructure\Messaging\FeatureFlagMessageMiddleware;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Query\GetUsers\GetUsersCursorQuery;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class FeatureFlagMessageMiddlewareTest extends UnitTestCase
{
    public function testItLetsGatedMessageThroughWhenFlagIsEnabled(): void
    {
        $featureFlags = $this->createMock(FeatureFlagRepositoryInterface::class);
        $featureFlags->expects($this->once())
            ->method('isEnabled')
            ->with('cursor_pagination')
            ->willReturn(true);

        $middleware = new FeatureFlagMessageMiddleware($featureFlags);
        $envelope = new Envelope($this->cursorQuery());

        $result = $middleware->handle($envelope, $this->terminatingStack($envelope));

        $this->assertSame($envelope, $result);
    }

    public function testItRejectsGatedMessageWhenFlagIsDisabled(): void
    {
        $featureFlags = $this->createStub(FeatureFlagRepositoryInterface::class);
        $featureFlags->method('isEnabled')->willReturn(false);

        $middleware = new FeatureFlagMessageMiddleware($featureFlags);
        $envelope = new Envelope($this->cursorQuery());

        $this->expectException(FeatureDisabledException::class);
        $middleware->handle($envelope, $this->terminatingStack($envelope));
    }

    public function testItSkipsMessagesThatAreNotFeatureGated(): void
    {
        $featureFlags = $this->createMock(FeatureFlagRepositoryInterface::class);
        $featureFlags->expects($this->never())->method('isEnabled');

        $middleware = new FeatureFlagMessageMiddleware($featureFlags);
        $envelope = new Envelope(new \stdClass());

        $middleware->handle($envelope, $this->terminatingStack($envelope));
    }

    private function cursorQuery(): GetUsersCursorQuery
    {
        return new GetUsersCursorQuery(
            filters: new Filters([], Order::default(), new Pagination()),
            cursorPagination: CursorPagination::fromRequest(null, 20),
        );
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
}
