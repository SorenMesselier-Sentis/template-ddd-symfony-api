<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Security;

use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Exception\UnauthenticatedException;
use App\Shared\Domain\Security\SubjectIdentityInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class HttpOwnerContext implements OwnerContextInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function ownerId(): OwnerId
    {
        $user = $this->security->getUser();

        if (!$user instanceof SubjectIdentityInterface) {
            throw UnauthenticatedException::create();
        }

        return OwnerId::fromString($user->subjectId());
    }

    public function roles(): array
    {
        return array_values($this->security->getUser()?->getRoles() ?? []);
    }

    public function isAuthenticated(): bool
    {
        return $this->security->getUser() instanceof SubjectIdentityInterface;
    }
}
