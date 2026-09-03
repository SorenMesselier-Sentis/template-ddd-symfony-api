<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Security;

use App\Shared\Domain\Exception\RateLimitExceededException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Mirrors App\User\Infrastructure\Security\AuthRateLimitListener, kept as a separate listener
 * (rather than extending that one) so ApiClient's Infrastructure never imports User's — see
 * deptrac's cross-BC Infrastructure rule.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 255)]
final class ApiClientRateLimitListener
{
    private const TOKEN_PATH = '/api/v1/oauth/token';

    public function __construct(
        private readonly RateLimiterFactory $oauthTokenLimiter,
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

        if ('POST' !== $request->getMethod() || self::TOKEN_PATH !== $request->getPathInfo()) {
            return;
        }

        $limiter = $this->oauthTokenLimiter->create($this->clientKey($request));

        if (!$limiter->consume()->isAccepted()) {
            throw RateLimitExceededException::create();
        }
    }

    private function clientKey(Request $request): string
    {
        return $request->getClientIp() ?? 'unknown';
    }
}
