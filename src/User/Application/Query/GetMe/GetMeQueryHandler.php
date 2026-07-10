<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetMe;

use App\User\Application\Query\GetUser\UserResponse;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Security\UserContextInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetMeQueryHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly UserContextInterface $userContext,
    ) {
    }

    public function __invoke(GetMeQuery $query): UserResponse
    {
        $user = $this->repository->findById($this->userContext->userId());

        if (null === $user) {
            throw UserNotFoundException::withId($this->userContext->userId()->value());
        }

        return new UserResponse($user);
    }
}
