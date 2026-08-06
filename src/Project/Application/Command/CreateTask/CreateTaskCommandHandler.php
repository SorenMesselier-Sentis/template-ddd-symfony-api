<?php

declare(strict_types=1);

namespace App\Project\Application\Command\CreateTask;

use App\Project\Domain\Entity\Task;
use App\Project\Domain\Exception\ProjectNotActiveException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Exception\TaskAlreadyExistsException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\Repository\TaskRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\AssigneeId;
use App\Project\Domain\ValueObject\AttachmentId;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\TaskId;
use App\Project\Domain\ValueObject\TaskTitle;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateTaskCommandHandler
{
    private const ADMIN_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly TaskRepositoryInterface $repository,
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly OwnerContextInterface $ownerContext,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateTaskCommand $command): void
    {
        $projectId = ProjectId::fromString($command->projectId);
        $project = $this->projectRepository->findById($projectId);

        if (null === $project) {
            throw ProjectNotFoundException::withId($command->projectId);
        }

        if (!$this->isAdmin() && !$project->ownerId()->equals($this->ownerContext->ownerId())) {
            throw ForbiddenException::create();
        }

        if (!$project->isActive()) {
            throw ProjectNotActiveException::withId($command->projectId);
        }

        $title = TaskTitle::fromString($command->title);

        if ($this->repository->existsByProjectIdAndTitle($projectId, $title)) {
            throw TaskAlreadyExistsException::withField('title', $command->title);
        }

        $this->logger->info('Creating Task', ['id' => $command->id, 'projectId' => $command->projectId]);

        $entity = Task::create(
            id: TaskId::fromString($command->id),
            project: $project,
            title: $title,
            assigneeId: null !== $command->assigneeId ? AssigneeId::fromString($command->assigneeId) : null,
            attachmentId: null !== $command->attachmentId ? AttachmentId::fromString($command->attachmentId) : null,
        );

        $this->repository->save($entity);
        $this->eventBus->publish(...$entity->pullDomainEvents());

        $this->logger->info('Task created', ['id' => $command->id]);
    }

    private function isAdmin(): bool
    {
        return in_array(self::ADMIN_ROLE, $this->ownerContext->roles(), true);
    }
}
