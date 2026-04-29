<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Filter\FilterOperator;
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

    public function __construct(private readonly EntityManagerInterface $em) {}

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

        if ($user !== null && $user->status() === UserStatus::DELETED) {
            return null;
        }

        return $user;
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
        return $this->findByEmail($email) !== null;
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
            ->select('COUNT(u.id)');

        foreach ($filters->all() as $i => $filter) {
            $param = sprintf('filter_%s_%d', $filter->field, $i);
            $field = sprintf('u.%s', $filter->field);

            match ($filter->operator) {
                FilterOperator::EQUAL => $qb
                    ->andWhere(sprintf('%s = :%s', $field, $param))
                    ->setParameter($param, $filter->value),
                default => null,
            };
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}