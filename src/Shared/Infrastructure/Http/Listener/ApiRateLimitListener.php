<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use App\Shared\Domain\Exception\RateLimitExceededException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: KernelEvents::CONTROLLER, method: 'onKernelController')]
final class ApiRateLimitListener
{
    public function __construct(
        private readonly RateLimiterFactory $apiDefaultLimiter,
        private readonly Security $security,
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (1 !== preg_match('#^/api/v\d+#', $request->getPathInfo())) {
            return;
        }

        $identity = $this->security->getUser()?->getUserIdentifier() ?? $request->getClientIp() ?? 'unknown';
        $rateLimit = $this->apiDefaultLimiter->create($identity)->consume();

        if (!$rateLimit->isAccepted()) {
            $retryAfterSeconds = max(0, $rateLimit->getRetryAfter()->getTimestamp() - time());

            throw RateLimitExceededException::create($retryAfterSeconds);
        }
    }
}
