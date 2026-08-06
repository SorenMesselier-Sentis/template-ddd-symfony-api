<?php

declare(strict_types=1);

namespace App\Project\Application\Command\CreateProject;

use App\Project\Domain\Entity\Project;
use App\Project\Domain\Exception\ProjectAlreadyExistsException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateProjectCommandHandler
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
        private readonly OwnerContextInterface $ownerContext,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateProjectCommand $command): void
    {
        $ownerId = $this->ownerContext->ownerId();
        $name = ProjectName::fromString($command->name);

        if ($this->repository->existsByOwnerIdAndName($ownerId, $name)) {
            throw ProjectAlreadyExistsException::withField('name', $command->name);
        }

        $this->logger->info('Creating Project', ['id' => $command->id]);

        $entity = Project::create(ProjectId::fromString($command->id), $ownerId, $name);

        $this->repository->save($entity);
        $this->eventBus->publish(...$entity->pullDomainEvents());

        $this->logger->info('Project created', ['id' => $command->id]);
    }
}
