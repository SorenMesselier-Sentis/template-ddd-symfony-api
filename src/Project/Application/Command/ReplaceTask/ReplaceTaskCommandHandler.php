<?php

declare(strict_types=1);

namespace App\Project\Application\Command\ReplaceTask;

use App\Project\Domain\Exception\TaskAlreadyExistsException;
use App\Project\Domain\Exception\TaskNotFoundException;
use App\Project\Domain\Repository\TaskRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\AssigneeId;
use App\Project\Domain\ValueObject\TaskId;
use App\Project\Domain\ValueObject\TaskTitle;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ReplaceTaskCommandHandler
{
    private const ADMIN_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly TaskRepositoryInterface $repository,
        private readonly OwnerContextInterface $ownerContext,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ReplaceTaskCommand $command): void
    {
        $entity = $this->repository->findById(TaskId::fromString($command->id));

        if (null === $entity) {
            throw TaskNotFoundException::withId($command->id);
        }

        if (!$this->isAdmin() && !$entity->project()->ownerId()->equals($this->ownerContext->ownerId())) {
            throw ForbiddenException::create();
        }

        $title = TaskTitle::fromString($command->title);

        if (!$title->equals($entity->title()) && $this->repository->existsByProjectIdAndTitle($entity->project()->id(), $title)) {
            throw TaskAlreadyExistsException::withField('title', $command->title);
        }

        $this->logger->info('Replacing Task', ['id' => $command->id]);

        $entity->replace(
            $title,
            null !== $command->assigneeId ? AssigneeId::fromString($command->assigneeId) : null,
        );

        $this->repository->save($entity);
        $this->eventBus->publish(...$entity->pullDomainEvents());

        $this->logger->info('Task replaced', ['id' => $command->id]);
    }

    private function isAdmin(): bool
    {
        return in_array(self::ADMIN_ROLE, $this->ownerContext->roles(), true);
    }
}
