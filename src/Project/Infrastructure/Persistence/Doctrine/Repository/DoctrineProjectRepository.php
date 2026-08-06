<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Persistence\Doctrine\Repository;

use App\Project\Domain\Entity\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\ValueObject\OwnerId;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectStatus;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineFilterApplier;
use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class DoctrineProjectRepository implements ProjectRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(Project $entity): void
    {
        $this->saveEntity($this->em, $entity);
    }

    public function findById(ProjectId $id): ?Project
    {
        $project = $this->em->find(Project::class, $id);

        if (null !== $project && ProjectStatus::DELETED === $project->status()) {
            return null;
        }

        return $project;
    }

    public function findByIdIncludingDeleted(ProjectId $id): ?Project
    {
        return $this->em->find(Project::class, $id);
    }

    public function existsByOwnerIdAndName(OwnerId $ownerId, ProjectName $name): bool
    {
        $count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(Project::class, 'e')
            ->where('e.ownerId = :ownerId')
            ->andWhere('e.name = :name')
            ->andWhere('e.status != :deleted')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('name', $name->value())
            ->setParameter('deleted', ProjectStatus::DELETED)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function findByOwnerIdAndFilters(OwnerId $ownerId, Filters $filters): array
    {
        $qb = $this->activeByOwnerQueryBuilder($ownerId);
        DoctrineFilterApplier::apply($qb, $filters, 'e');

        /* @var list<Project> */
        return $qb->getQuery()->getResult();
    }

    public function countByOwnerIdAndFilters(OwnerId $ownerId, Filters $filters): int
    {
        $qb = $this->activeByOwnerQueryBuilder($ownerId)
            ->select('COUNT(e.id)');

        DoctrineFilterApplier::applyFilters($qb, $filters, 'e');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function activeByOwnerQueryBuilder(OwnerId $ownerId): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from(Project::class, 'e')
            ->where('e.ownerId = :ownerId')
            ->andWhere('e.status != :deleted')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('deleted', ProjectStatus::DELETED);
    }
}
