<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Infrastructure\Http\Security\PublicRoutes;
use App\Shared\Infrastructure\Http\Security\RouteRoles;
use App\User\Domain\Exception\InvalidTokenException;
use App\User\Domain\Exception\MissingTokenException;
use App\User\Domain\Service\TokenServiceInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class JwtAuthenticationListener
{
    public function __construct(
        private readonly TokenServiceInterface $tokenService,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $method = $request->getMethod();
        $path = $request->getPathInfo();

        if (PublicRoutes::isPublic($method, $path)) {
            return;
        }

        $authHeader = $request->headers->get('Authorization', '');

        if (empty($authHeader)) {
            throw MissingTokenException::create();
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw InvalidTokenException::create();
        }

        $token = trim(substr($authHeader, 7));

        if (empty($token)) {
            throw InvalidTokenException::create();
        }

        $claims = $this->tokenService->decodeAccessToken($token);

        if (RouteRoles::requiresAdmin($method, $path)) {
            if (!in_array('ROLE_ADMIN', $claims->roles, true)) {
                throw ForbiddenException::create();
            }
        }
    }
}
