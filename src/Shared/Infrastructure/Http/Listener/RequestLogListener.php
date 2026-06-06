<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Infrastructure\Http\RequestContext;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

final class RequestLogListener
{
    private float $startedAt = 0.0;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RequestContext $requestContext,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->startedAt = microtime(true);
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$event->isMainRequest() || 0.0 === $this->startedAt) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $this->logger->info('http.request', [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => (int) round((microtime(true) - $this->startedAt) * 1000),
            'request_id' => $this->requestContext->requestId(),
        ]);
    }
}
