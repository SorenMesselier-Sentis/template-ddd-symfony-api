<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Persistence\Doctrine\Repository;

use App\Project\Domain\Entity\Project;
use App\Project\Domain\Entity\Task;
use App\Project\Domain\Repository\TaskRepositoryInterface;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\TaskId;
use App\Project\Domain\ValueObject\TaskStatus;
use App\Project\Domain\ValueObject\TaskTitle;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineFilterApplier;
use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class DoctrineTaskRepository implements TaskRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(Task $entity): void
    {
        $this->saveEntity($this->em, $entity);
    }

    public function findById(TaskId $id): ?Task
    {
        $task = $this->em->find(Task::class, $id);

        if (null !== $task && TaskStatus::DELETED === $task->status()) {
            return null;
        }

        return $task;
    }

    public function findByIdIncludingDeleted(TaskId $id): ?Task
    {
        return $this->em->find(Task::class, $id);
    }

    public function existsByProjectIdAndTitle(ProjectId $projectId, TaskTitle $title): bool
    {
        $count = (int) $this->byProjectQueryBuilder($projectId)
            ->select('COUNT(e.id)')
            ->andWhere('e.title = :title')
            ->setParameter('title', $title->value())
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function countActiveByProjectId(ProjectId $projectId): int
    {
        return (int) $this->byProjectQueryBuilder($projectId)
            ->select('COUNT(e.id)')
            ->andWhere('e.status IN (:activeStatuses)')
            ->setParameter('activeStatuses', [TaskStatus::TODO, TaskStatus::IN_PROGRESS])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByProjectIdAndFilters(ProjectId $projectId, Filters $filters): array
    {
        $qb = $this->byProjectQueryBuilder($projectId);
        DoctrineFilterApplier::apply($qb, $filters, 'e');

        /* @var list<Task> */
        return $qb->getQuery()->getResult();
    }

    public function countByProjectIdAndFilters(ProjectId $projectId, Filters $filters): int
    {
        $qb = $this->byProjectQueryBuilder($projectId)
            ->select('COUNT(e.id)');

        DoctrineFilterApplier::applyFilters($qb, $filters, 'e');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function byProjectQueryBuilder(ProjectId $projectId): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from(Task::class, 'e')
            ->where('e.project = :project')
            ->andWhere('e.status != :deleted')
            ->setParameter('project', $this->em->getReference(Project::class, $projectId))
            ->setParameter('deleted', TaskStatus::DELETED);
    }
}
