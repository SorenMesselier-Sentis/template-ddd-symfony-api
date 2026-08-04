<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Listener;

use App\Shared\Domain\Exception\RateLimitExceededException;
use App\Shared\Infrastructure\Http\Listener\ApiRateLimitListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Core\User\UserInterface;

final class ApiRateLimitListenerTest extends UnitTestCase
{
    public function testRequestsWithinLimitAreAllowed(): void
    {
        $listener = $this->createListener(limit: 2);

        $listener->onKernelController($this->createControllerEvent('/api/v1/users'));
        $listener->onKernelController($this->createControllerEvent('/api/v1/users'));

        $this->addToAssertionCount(2);
    }

    public function testRequestBeyondLimitThrowsWithRetryAfter(): void
    {
        $listener = $this->createListener(limit: 1);

        $listener->onKernelController($this->createControllerEvent('/api/v1/users'));

        try {
            $listener->onKernelController($this->createControllerEvent('/api/v1/users'));
            $this->fail('Expected RateLimitExceededException.');
        } catch (RateLimitExceededException $exception) {
            $this->assertGreaterThanOrEqual(0, $exception->retryAfterSeconds);
        }
    }

    public function testNonApiPathsAreNotThrottled(): void
    {
        $listener = $this->createListener(limit: 1);

        $listener->onKernelController($this->createControllerEvent('/health'));
        $listener->onKernelController($this->createControllerEvent('/health'));

        $this->addToAssertionCount(2);
    }

    public function testAuthenticatedUserIsKeyedByIdentityRegardlessOfClientIp(): void
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('user-42');

        $listener = $this->createListener(limit: 1, user: $user);

        $listener->onKernelController($this->createControllerEvent('/api/v1/users', '203.0.113.1'));

        $this->expectException(RateLimitExceededException::class);

        // Same authenticated user from a different client IP still shares the budget.
        $listener->onKernelController($this->createControllerEvent('/api/v1/users', '203.0.113.2'));
    }

    public function testDistinctClientIpsGetIndependentBudgets(): void
    {
        $listener = $this->createListener(limit: 1);

        $listener->onKernelController($this->createControllerEvent('/api/v1/users', '203.0.113.1'));
        $listener->onKernelController($this->createControllerEvent('/api/v1/users', '203.0.113.2'));

        $this->addToAssertionCount(2);
    }

    private function createListener(int $limit, ?UserInterface $user = null): ApiRateLimitListener
    {
        $factory = new RateLimiterFactory(
            ['id' => 'api_default', 'policy' => 'sliding_window', 'limit' => $limit, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        return new ApiRateLimitListener($factory, $security);
    }

    private function createControllerEvent(string $path, string $clientIp = '203.0.113.1'): ControllerEvent
    {
        $request = Request::create($path);
        $request->server->set('REMOTE_ADDR', $clientIp);

        return new ControllerEvent(
            $this->createStub(HttpKernelInterface::class),
            static fn () => null,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}
