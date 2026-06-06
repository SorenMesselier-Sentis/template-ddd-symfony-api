<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Security;

use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Exception\UnauthenticatedException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class HttpOwnerContext implements OwnerContextInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public function ownerId(): OwnerId
    {
        $payload = $this->payload();

        if (!isset($payload['sub']) || !is_string($payload['sub'])) {
            throw UnauthenticatedException::create();
        }

        return OwnerId::fromString($payload['sub']);
    }

    public function roles(): array
    {
        $payload = $this->payload();
        $roles = $payload['roles'] ?? [];

        if (!is_array($roles)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $role): string => (string) $role, $roles));
    }

    public function isAuthenticated(): bool
    {
        try {
            $this->payload();

            return true;
        } catch (UnauthenticatedException) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw UnauthenticatedException::create();
        }

        $authHeader = $request->headers->get('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw UnauthenticatedException::create();
        }

        $token = trim(substr($authHeader, 7));

        try {
            return $this->jwtManager->parse($token);
        } catch (\Throwable) {
            throw UnauthenticatedException::create();
        }
    }
}
