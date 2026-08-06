<?php

declare(strict_types=1);

namespace App\Project\Application\Command\DeleteTask;

use App\Project\Domain\Exception\TaskNotFoundException;
use App\Project\Domain\Repository\TaskRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\TaskId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteTaskCommandHandler
{
    private const ADMIN_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly TaskRepositoryInterface $repository,
        private readonly OwnerContextInterface $ownerContext,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeleteTaskCommand $command): void
    {
        $entity = $this->repository->findById(TaskId::fromString($command->id));

        if (null === $entity) {
            throw TaskNotFoundException::withId($command->id);
        }

        if (!$this->isAdmin() && !$entity->project()->ownerId()->equals($this->ownerContext->ownerId())) {
            throw ForbiddenException::create();
        }

        $this->logger->info('Deleting Task', ['id' => $command->id]);

        $entity->delete();

        $this->repository->save($entity);
        $this->eventBus->publish(...$entity->pullDomainEvents());

        $this->logger->info('Task deleted', ['id' => $command->id]);
    }

    private function isAdmin(): bool
    {
        return in_array(self::ADMIN_ROLE, $this->ownerContext->roles(), true);
    }
}
