<?php

declare(strict_types=1);

namespace App\Project\Application\Command\DeleteProject;

use App\Project\Domain\Exception\ProjectHasActiveTasksException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\Repository\TaskRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\ProjectId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteProjectCommandHandler
{
    private const ADMIN_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly OwnerContextInterface $ownerContext,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeleteProjectCommand $command): void
    {
        $projectId = ProjectId::fromString($command->id);
        $entity = $this->repository->findById($projectId);

        if (null === $entity) {
            throw ProjectNotFoundException::withId($command->id);
        }

        if (!$this->isAdmin() && !$entity->ownerId()->equals($this->ownerContext->ownerId())) {
            throw ForbiddenException::create();
        }

        if ($this->taskRepository->countActiveByProjectId($projectId) > 0) {
            throw ProjectHasActiveTasksException::withId($command->id);
        }

        $this->logger->info('Deleting Project', ['id' => $command->id]);

        $entity->delete();

        $this->repository->save($entity);
        $this->eventBus->publish(...$entity->pullDomainEvents());

        $this->logger->info('Project deleted', ['id' => $command->id]);
    }

    private function isAdmin(): bool
    {
        return in_array(self::ADMIN_ROLE, $this->ownerContext->roles(), true);
    }
}
