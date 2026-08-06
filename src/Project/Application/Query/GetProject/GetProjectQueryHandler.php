<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetProject;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\ProjectId;
use App\Shared\Domain\Exception\ForbiddenException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetProjectQueryHandler
{
    private const ADMIN_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
        private readonly OwnerContextInterface $ownerContext,
    ) {
    }

    public function __invoke(GetProjectQuery $query): ProjectResponse
    {
        $entity = $this->repository->findById(ProjectId::fromString($query->id));

        if (null === $entity) {
            throw ProjectNotFoundException::withId($query->id);
        }

        if (!$this->isAdmin() && !$entity->ownerId()->equals($this->ownerContext->ownerId())) {
            throw ForbiddenException::create();
        }

        return new ProjectResponse($entity);
    }

    private function isAdmin(): bool
    {
        return in_array(self::ADMIN_ROLE, $this->ownerContext->roles(), true);
    }
}
