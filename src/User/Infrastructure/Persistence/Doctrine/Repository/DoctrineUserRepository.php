<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(private readonly EntityManagerInterface $em) {}

    public function save(User $user): void
    {
        $this->saveEntity($this->em, $user);
    }

    public function findById(UserId $id): ?User
    {
        return $this->em->find(User::class, $id);
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->em
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function findAll(int $page, int $perPage): array
    {
        $qb = $this->em->getRepository(User::class)
        ->createQueryBuilder('u')
        ->orderBy('u.email', 'ASC');

        return $this->paginate($qb, $page, $perPage);
    }

    public function count(): int
    {
        return (int) $this->em
        ->getRepository(User::class)
        ->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->getQuery()
        ->getSingleScalarResult();
    }
}
