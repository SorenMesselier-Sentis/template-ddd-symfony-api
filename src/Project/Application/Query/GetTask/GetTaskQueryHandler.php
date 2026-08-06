<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetTask;

use App\Project\Domain\Exception\TaskNotFoundException;
use App\Project\Domain\Repository\TaskRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\TaskId;
use App\Shared\Domain\Exception\ForbiddenException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetTaskQueryHandler
{
    private const ADMIN_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly TaskRepositoryInterface $repository,
        private readonly OwnerContextInterface $ownerContext,
    ) {
    }

    public function __invoke(GetTaskQuery $query): TaskResponse
    {
        $entity = $this->repository->findById(TaskId::fromString($query->id));

        if (null === $entity) {
            throw TaskNotFoundException::withId($query->id);
        }

        if (!$this->isAdmin() && !$entity->project()->ownerId()->equals($this->ownerContext->ownerId())) {
            throw ForbiddenException::create();
        }

        return new TaskResponse($entity);
    }

    private function isAdmin(): bool
    {
        return in_array(self::ADMIN_ROLE, $this->ownerContext->roles(), true);
    }
}
