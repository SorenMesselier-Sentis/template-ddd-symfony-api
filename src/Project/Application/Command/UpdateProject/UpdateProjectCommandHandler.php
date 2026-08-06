<?php

declare(strict_types=1);

namespace App\Project\Application\Command\UpdateProject;

use App\Project\Domain\Exception\InvalidProjectStatusException;
use App\Project\Domain\Exception\ProjectAlreadyExistsException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectStatus;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateProjectCommandHandler
{
    private const ADMIN_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
        private readonly OwnerContextInterface $ownerContext,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateProjectCommand $command): void
    {
        $entity = $this->repository->findById(ProjectId::fromString($command->id));

        if (null === $entity) {
            throw ProjectNotFoundException::withId($command->id);
        }

        if (!$this->isAdmin() && !$entity->ownerId()->equals($this->ownerContext->ownerId())) {
            throw ForbiddenException::create();
        }

        $name = null;

        if (null !== $command->name) {
            $name = ProjectName::fromString($command->name);

            if (!$name->equals($entity->name()) && $this->repository->existsByOwnerIdAndName($entity->ownerId(), $name)) {
                throw ProjectAlreadyExistsException::withField('name', $command->name);
            }
        }

        $status = null;

        if (null !== $command->status) {
            $status = ProjectStatus::tryFrom($command->status);

            if (null === $status || ProjectStatus::DELETED === $status) {
                throw InvalidProjectStatusException::withValue($command->status);
            }
        }

        $this->logger->info('Updating Project', ['id' => $command->id]);

        $entity->update($name, $status);

        $this->repository->save($entity);
        $this->eventBus->publish(...$entity->pullDomainEvents());

        $this->logger->info('Project updated', ['id' => $command->id]);
    }

    private function isAdmin(): bool
    {
        return in_array(self::ADMIN_ROLE, $this->ownerContext->roles(), true);
    }
}
