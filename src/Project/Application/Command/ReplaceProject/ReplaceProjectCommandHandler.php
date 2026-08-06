<?php

declare(strict_types=1);

namespace App\Project\Application\Command\ReplaceProject;

use App\Project\Domain\Exception\ProjectAlreadyExistsException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ReplaceProjectCommandHandler
{
    private const ADMIN_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
        private readonly OwnerContextInterface $ownerContext,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ReplaceProjectCommand $command): void
    {
        $entity = $this->repository->findById(ProjectId::fromString($command->id));

        if (null === $entity) {
            throw ProjectNotFoundException::withId($command->id);
        }

        if (!$this->isAdmin() && !$entity->ownerId()->equals($this->ownerContext->ownerId())) {
            throw ForbiddenException::create();
        }

        $name = ProjectName::fromString($command->name);

        if (!$name->equals($entity->name()) && $this->repository->existsByOwnerIdAndName($entity->ownerId(), $name)) {
            throw ProjectAlreadyExistsException::withField('name', $command->name);
        }

        $this->logger->info('Replacing Project', ['id' => $command->id]);

        $entity->replace($name);

        $this->repository->save($entity);
        $this->eventBus->publish(...$entity->pullDomainEvents());

        $this->logger->info('Project replaced', ['id' => $command->id]);
    }

    private function isAdmin(): bool
    {
        return in_array(self::ADMIN_ROLE, $this->ownerContext->roles(), true);
    }
}
