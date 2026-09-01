<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Filter\Cursor;
use App\Shared\Domain\Filter\CursorPage;
use App\Shared\Domain\Filter\CursorPagination;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineFilterApplier;
use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserStatus;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function save(User $user): void
    {
        $this->saveEntity($this->em, $user);
    }

    public function delete(User $user): void
    {
        $this->deleteEntity($this->em, $user);
    }

    public function findById(UserId $id): ?User
    {
        /** @var User|null $user */
        $user = $this->em->find(User::class, $id);

        if (null !== $user && UserStatus::DELETED === $user->status()) {
            return null;
        }

        return $user;
    }

    public function findByIdIncludingDeleted(UserId $id): ?User
    {
        $user = $this->em->find(User::class, $id);

        return $user instanceof User ? $user : null;
    }

    public function findByEmail(Email $email): ?User
    {
        /** @var User|null $user */
        $user = $this->em
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        return $user;
    }

    public function existsByEmail(Email $email): bool
    {
        return null !== $this->findByEmail($email);
    }

    /** @return array<int, User> */
    public function findByFilters(Filters $filters): array
    {
        $qb = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->andWhere('u.status != :deleted')
            ->setParameter('deleted', UserStatus::DELETED->value);

        DoctrineFilterApplier::apply($qb, $filters, 'u');

        return $qb->getQuery()->getResult();
    }

    public function countByFilters(Filters $filters): int
    {
        $qb = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.status != :deleted')
            ->setParameter('deleted', UserStatus::DELETED->value);

        DoctrineFilterApplier::applyFilters($qb, $filters, 'u');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @return CursorPage<User> */
    public function findByFiltersCursor(Filters $filters, CursorPagination $cursorPagination): CursorPage
    {
        $qb = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->andWhere('u.status != :deleted')
            ->setParameter('deleted', UserStatus::DELETED->value);

        DoctrineFilterApplier::applyFilters($qb, $filters, 'u');

        if (null !== $cursorPagination->after) {
            $qb->andWhere('(u.createdAt < :cursorCreatedAt) OR (u.createdAt = :cursorCreatedAt AND u.id < :cursorId)')
                ->setParameter('cursorCreatedAt', $cursorPagination->after->createdAt)
                ->setParameter('cursorId', UserId::fromString($cursorPagination->after->id));
        }

        $qb->orderBy('u.createdAt', 'DESC')
            ->addOrderBy('u.id', 'DESC')
            ->setMaxResults($cursorPagination->limit + 1);

        /** @var list<User> $rows */
        $rows = $qb->getQuery()->getResult();

        $hasMore = \count($rows) > $cursorPagination->limit;
        $items = \array_slice($rows, 0, $cursorPagination->limit);

        $nextCursor = null;
        $last = end($items);

        if ($hasMore && false !== $last) {
            $nextCursor = (new Cursor($last->createdAt(), $last->id()->value()))->encode();
        }

        return new CursorPage($items, $nextCursor);
    }
}
