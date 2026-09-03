<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Persistence\Doctrine\Repository;

use App\ApiClient\Domain\Entity\IssuedAccessToken;
use App\ApiClient\Domain\Repository\IssuedAccessTokenRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineIssuedAccessTokenRepository implements IssuedAccessTokenRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(IssuedAccessToken $token): void
    {
        $this->saveEntity($this->em, $token);
    }

    public function findById(string $id): ?IssuedAccessToken
    {
        return $this->em->find(IssuedAccessToken::class, $id);
    }

    public function revoke(string $id): void
    {
        $this->em->getRepository(IssuedAccessToken::class)
            ->createQueryBuilder('t')
            ->update()
            ->set('t.revoked', 'true')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    public function revokeAllForClient(string $apiClientId): void
    {
        $this->em->getRepository(IssuedAccessToken::class)
            ->createQueryBuilder('t')
            ->update()
            ->set('t.revoked', 'true')
            ->where('IDENTITY(t.apiClient) = :apiClientId')
            ->setParameter('apiClientId', $apiClientId)
            ->getQuery()
            ->execute();
    }

    public function deleteExpired(\DateTimeImmutable $now): int
    {
        $deleted = $this->em->getRepository(IssuedAccessToken::class)
            ->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();

        return $deleted;
    }
}
