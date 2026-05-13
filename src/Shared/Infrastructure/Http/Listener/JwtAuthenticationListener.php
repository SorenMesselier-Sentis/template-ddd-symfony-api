<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use App\User\Domain\Exception\InvalidTokenException;
use App\User\Domain\Service\TokenServiceInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class JwtAuthenticationListener
{
    private const PROTECTED_ROUTES = [
        '/api/v1/auth/logout',
    ];

    public function __construct(
            private readonly TokenServiceInterface $tokenService,
        ) {}

        public function onKernelRequest(RequestEvent $event): void
        {
            if (!$event->isMainRequest()) {
                return;
            }

            $request = $event->getRequest();

            if (!$this->isProtectedRoute($request->getPathInfo())) {
                return;
            }

            $authHeader = $request->headers->get('Authorization', '');

            if (!str_starts_with($authHeader, 'Bearer ')) {
                throw InvalidTokenException::create();
            }

            $token = substr($authHeader, 7);

            $this->tokenService->decodeAccessToken($token);
        }

        private function isProtectedRoute(string $path): bool
        {
            foreach (self::PROTECTED_ROUTES as $route) {
                if (str_starts_with($path, $route)) {
                    return true;
                }
            }

            return false;
        }

}
