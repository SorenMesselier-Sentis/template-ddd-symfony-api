<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class ApiHeadersListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-API-Version', 'v1');
    }
}
