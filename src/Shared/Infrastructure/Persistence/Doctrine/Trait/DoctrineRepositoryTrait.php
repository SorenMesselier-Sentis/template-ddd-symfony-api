<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Trait;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

trait DoctrineRepositoryTrait
{
    protected function saveEntity(EntityManager $em, object $entity): void
    {
        $em->persist($entity);
        $em->flush();
    }

    protected function deleteEntity(EntityManager $em, object $entity): void
    {
        $em->remove($entity);
        $em->flush();
    }

    protected function paginate(QueryBuilder $qb, int $page, int $perPage): array
    {
        return $qb
        ->setFirstResult(($page - 1) * $perPage)
        ->setMaxResults($perPage)
        ->getQuery()
        ->getResult();
    }
}
