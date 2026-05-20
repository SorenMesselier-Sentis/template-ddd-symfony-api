<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Exception\MissingTokenException;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\Service\TokenServiceInterface;
use App\User\Domain\ValueObject\TokenClaims;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\HttpFoundation\RequestStack;

final class HttpUserContext implements UserContextInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TokenServiceInterface $tokenService,
    ) {}

    public function userId(): UserId
    {
        return UserId::fromString($this->claims()->sub);
    }

    public function roles(): array
    {
        return $this->claims()->roles;
    }

    private function claims(): TokenClaims
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw MissingTokenException::create();
        }

        $authHeader = $request->headers->get('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw MissingTokenException::create();
        }

        $token = trim(substr($authHeader, 7));

        return $this->tokenService->decodeAccessToken($token);
    }
}
