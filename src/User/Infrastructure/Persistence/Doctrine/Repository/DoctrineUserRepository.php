<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    public function findById(UserId $id): ?User
    {
        return $this->em->find(User::class, $id);
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->findByEmail($email) !== null;
    }
}
