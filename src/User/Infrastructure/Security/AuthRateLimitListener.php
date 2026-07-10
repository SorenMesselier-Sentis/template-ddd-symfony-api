<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Exception\RateLimitExceededException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 255)]
final class AuthRateLimitListener
{
    public function __construct(
        private readonly RateLimiterFactory $authLoginLimiter,
        private readonly RateLimiterFactory $authForgotPasswordLimiter,
        private readonly RateLimiterFactory $authRegisterLimiter,
        private readonly string $environment,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ('test' === $this->environment) {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ('POST' !== $request->getMethod()) {
            return;
        }

        $limiterName = match ($request->getPathInfo()) {
            '/api/v1/auth/login' => 'login',
            '/api/v1/auth/forgot-password' => 'forgot_password',
            '/api/v1/auth/register' => 'register',
            default => null,
        };

        if (null === $limiterName) {
            return;
        }

        $limiter = match ($limiterName) {
            'login' => $this->authLoginLimiter->create($request->getClientIp() ?? 'unknown'),
            'forgot_password' => $this->authForgotPasswordLimiter->create($request->getClientIp() ?? 'unknown'),
            'register' => $this->authRegisterLimiter->create($request->getClientIp() ?? 'unknown'),
        };

        if (!$limiter->consume()->isAccepted()) {
            throw RateLimitExceededException::create();
        }
    }
}
