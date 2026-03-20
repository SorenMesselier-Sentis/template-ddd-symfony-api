<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUser;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserQueryHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function __invoke(GetUserQuery $query): UserResponse
    {
        $user = $this->repository->findById(UserId::fromString($query->id));

        if ($user === null) {
            throw UserNotFoundException::withId($query->id);
        }

        return new UserResponse($user);
    }
}
