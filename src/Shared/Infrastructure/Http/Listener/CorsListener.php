<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class CorsListener
{
    private const string ALLOWED_HEADERS = 'Content-Type, Authorization, X-Request-Id';
    private const string EXPOSED_HEADERS = 'Location, X-Request-Id';

    public function __construct(
        private readonly string $allowedOriginsRaw,
        private readonly string $allowedMethods,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (Request::METHOD_OPTIONS !== $request->getMethod()) {
            return;
        }

        $response = new Response(status: Response::HTTP_NO_CONTENT);
        $this->applyCorsHeaders($request, $response);
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->applyCorsHeaders($event->getRequest(), $event->getResponse());
    }

    private function applyCorsHeaders(Request $request, Response $response): void
    {
        $origin = $request->headers->get('Origin');

        if (!\is_string($origin) || '' === $origin) {
            return;
        }

        $allowedOrigin = $this->resolveAllowedOrigin($origin);

        if (null === $allowedOrigin) {
            return;
        }

        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', $this->allowedMethods);
        $response->headers->set('Access-Control-Allow-Headers', self::ALLOWED_HEADERS);
        $response->headers->set('Access-Control-Expose-Headers', self::EXPOSED_HEADERS);
        $response->headers->set('Access-Control-Max-Age', '86400');

        if ('*' !== $allowedOrigin) {
            $response->setVary('Origin', false);
        }
    }

    private function resolveAllowedOrigin(string $origin): ?string
    {
        if ('*' === $this->allowedOriginsRaw) {
            return '*';
        }

        $allowedOrigins = array_values(array_filter(
            array_map(trim(...), explode(',', $this->allowedOriginsRaw)),
            static fn (string $origin): bool => '' !== $origin,
        ));

        if (\in_array($origin, $allowedOrigins, true)) {
            return $origin;
        }

        return null;
    }
}
