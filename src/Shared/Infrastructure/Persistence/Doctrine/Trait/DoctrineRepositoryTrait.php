<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

trait DoctrineRepositoryTrait
{
    protected function saveEntity(EntityManagerInterface $em, object $entity): void
    {
        $em->persist($entity);
        $em->flush();
    }

    protected function deleteEntity(EntityManagerInterface $em, object $entity): void
    {
        $em->remove($entity);
        $em->flush();
    }

    /**
     * @return list<mixed>
     */
    protected function paginate(QueryBuilder $qb, int $page, int $perPage): array
    {
        $results = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        if (!\is_array($results)) {
            return [];
        }

        /* @var list<mixed> */
        return array_values($results);
    }
}
