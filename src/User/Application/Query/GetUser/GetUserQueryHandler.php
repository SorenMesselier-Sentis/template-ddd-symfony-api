<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUser;

use App\User\Application\Security\UserOwnershipGuard;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserQueryHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly UserOwnershipGuard $ownershipGuard,
    ) {
    }

    public function __invoke(GetUserQuery $query): UserResponse
    {
        $this->ownershipGuard->assertCanAccessUser($query->id);
        $user = $this->repository->findById(UserId::fromString($query->id));

        if (null === $user) {
            throw UserNotFoundException::withId($query->id);
        }

        return new UserResponse($user);
    }
}
