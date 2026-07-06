<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUserRoles;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserRole;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateUserRolesCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateUserRolesCommand $command): void
    {
        $this->logger->info('Updating user roles', ['id' => $command->id]);

        $user = $this->repository->findById(UserId::fromString($command->id));

        if (null === $user) {
            throw UserNotFoundException::withId($command->id);
        }

        $roles = array_map(
            fn (string $role) => UserRole::from($role),
            $command->roles,
        );

        // Be sure that the USER_ROLE is present at any cases
        if (!in_array(UserRole::USER, $roles, true)) {
            $roles[] = UserRole::USER;
        }

        $user->updateRoles(array_values($roles));

        $this->repository->save($user);
        $this->eventBus->publish(...$user->pullDomainEvents());

        $this->logger->info('User roles updated', [
            'id' => $command->id,
            'roles' => $command->roles,
        ]);
    }
}
