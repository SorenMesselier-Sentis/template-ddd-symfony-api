<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use App\User\Domain\Entity\RefreshToken as RefreshTokenEntity;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function save(RefreshTokenEntity $token): void
    {
        $this->saveEntity($this->em, $token);
    }

    public function findByToken(string $token): ?RefreshTokenEntity
    {
        return $this->em->getRepository(RefreshTokenEntity::class)->findOneBy(['token' => $token]);
    }

    public function revokeAllForUser(string $userId): void
    {
        $this->em->getRepository(RefreshTokenEntity::class)
            ->createQueryBuilder('rt')
            ->update()
            ->set('rt.revoked', 'true')
            ->where('rt.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function deleteExpired(\DateTimeImmutable $now): int
    {
        $deleted = $this->em->getRepository(RefreshTokenEntity::class)
            ->createQueryBuilder('rt')
            ->delete()
            ->where('rt.expiresAt < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();

        return (int) $deleted;
    }
}
