<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use App\User\Domain\Entity\EmailVerificationToken as EmailVerificationTokenEntity;
use App\User\Domain\Repository\EmailVerificationTokenRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineEmailVerificationTokenRepository implements EmailVerificationTokenRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function save(EmailVerificationTokenEntity $token): void
    {
        $this->saveEntity($this->em, $token);
    }

    public function findByToken(string $token): ?EmailVerificationTokenEntity
    {
        return $this->em->getRepository(EmailVerificationTokenEntity::class)->findOneBy(['token' => $token]);
    }

    public function revokeAllForUser(string $userId): void
    {
        $this->em->getRepository(EmailVerificationTokenEntity::class)
            ->createQueryBuilder('evt')
            ->update()
            ->set('evt.revoked', 'true')
            ->where('evt.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function deleteExpired(\DateTimeImmutable $now): int
    {
        $deleted = $this->em->getRepository(EmailVerificationTokenEntity::class)
            ->createQueryBuilder('evt')
            ->delete()
            ->where('evt.expiresAt < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();

        return $deleted;
    }
}
