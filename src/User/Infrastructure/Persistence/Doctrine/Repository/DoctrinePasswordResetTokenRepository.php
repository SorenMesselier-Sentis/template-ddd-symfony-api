<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use App\User\Domain\Entity\PasswordResetToken as PasswordResetTokenEntity;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function save(PasswordResetTokenEntity $token): void
    {
        $this->saveEntity($this->em, $token);
    }

    public function findByToken(string $token): ?PasswordResetTokenEntity
    {
        return $this->em->getRepository(PasswordResetTokenEntity::class)->findOneBy(['token' => $token]);
    }

    public function revokeAllForUser(string $userId): void
    {
        $this->em->getRepository(PasswordResetTokenEntity::class)
            ->createQueryBuilder('prt')
            ->update()
            ->set('prt.revoked', 'true')
            ->where('prt.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function deleteExpired(\DateTimeImmutable $now): int
    {
        $deleted = $this->em->getRepository(PasswordResetTokenEntity::class)
            ->createQueryBuilder('prt')
            ->delete()
            ->where('prt.expiresAt < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();

        return (int) $deleted;
    }
}
