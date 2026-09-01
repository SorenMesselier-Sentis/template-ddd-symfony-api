<?php

declare(strict_types=1);

namespace App\User\Application\Command\DeleteUser;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Privacy\PersonalDataEraserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteUserCommandHandler
{
    /**
     * @param iterable<PersonalDataEraserInterface> $erasers
     */
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly iterable $erasers,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeleteUserCommand $command): void
    {
        $this->logger->info('Deleting user', ['id' => $command->id]);

        $user = $this->repository->findById(UserId::fromString($command->id));

        if (null === $user) {
            throw UserNotFoundException::withId($command->id);
        }

        // Deletion doubles as GDPR erasure: UserPersonalDataEraser (one of the tagged erasers)
        // owns the status transition, field anonymization, event publishing and token
        // revocation; every other bounded context's eraser (e.g. Document's) runs alongside it.
        foreach ($this->erasers as $eraser) {
            $eraser->erase($command->id);
        }

        $this->logger->info('User deleted', ['id' => $command->id]);
    }
}
