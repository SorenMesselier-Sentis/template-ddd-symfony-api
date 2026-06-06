<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class ApiHeadersListener
{
    public function __construct(
        private readonly string $appEnv,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $response = $event->getResponse();

        if ('prod' === $this->appEnv) {
            $this->setHeaderIfMissing($response, 'Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (1 === preg_match('#^/api/doc(?:\.json)?$#', $path)) {
            $this->setHeaderIfMissing($response, 'X-Frame-Options', 'DENY');

            if (str_starts_with($path, '/api/doc') && !str_ends_with($path, '.json')) {
                $this->setHeaderIfMissing(
                    $response,
                    'Content-Security-Policy',
                    "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:",
                );
            }

            return;
        }

        if (1 !== preg_match('#^/api/(v\d+)#', $path, $matches)) {
            return;
        }

        $response->headers->set('Content-Type', 'application/json');
        $this->setHeaderIfMissing($response, 'X-Content-Type-Options', 'nosniff');
        $this->setHeaderIfMissing($response, 'X-Frame-Options', 'DENY');
        $this->setHeaderIfMissing($response, 'Referrer-Policy', 'no-referrer');
        $response->headers->set('X-API-Version', $matches[1]);
    }

    private function setHeaderIfMissing(\Symfony\Component\HttpFoundation\Response $response, string $name, string $value): void
    {
        if (!$response->headers->has($name)) {
            $response->headers->set($name, $value);
        }
    }
}
