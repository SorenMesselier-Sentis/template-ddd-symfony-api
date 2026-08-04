<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds a strong ETag to successful /api/v1 GET responses and short-circuits
 * to 304 Not Modified when the client's If-None-Match still matches, so a
 * revalidated resource never re-transfers its body. Runs last (after CORS,
 * correlation and API headers listeners) so setNotModified()'s header
 * stripping is not undone by a later listener re-adding Content-Type.
 */
#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onKernelResponse', priority: -10)]
final class ConditionalGetListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isMethod(Request::METHOD_GET)) {
            return;
        }

        if (1 !== preg_match('#^/api/v\d+#', $request->getPathInfo())) {
            return;
        }

        $response = $event->getResponse();

        if (!$response->isSuccessful()) {
            return;
        }

        $content = $response->getContent();

        if (false === $content || '' === $content) {
            return;
        }

        $response->setEtag(md5($content));
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-cache');

        $response->isNotModified($request);
    }
}
