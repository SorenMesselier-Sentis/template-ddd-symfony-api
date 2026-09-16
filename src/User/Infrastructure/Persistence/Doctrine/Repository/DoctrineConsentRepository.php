<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use App\User\Domain\Entity\Consent;
use App\User\Domain\Repository\ConsentRepositoryInterface;
use App\User\Domain\ValueObject\ConsentType;
use App\User\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineConsentRepository implements ConsentRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function save(Consent $consent): void
    {
        $this->saveEntity($this->em, $consent);
    }

    public function findByUserIdAndType(UserId $userId, ConsentType $type): ?Consent
    {
        /** @var Consent|null $consent */
        $consent = $this->em->getRepository(Consent::class)->findOneBy([
            'userId' => $userId,
            'type' => $type,
        ]);

        return $consent;
    }

    /** @return array<int, Consent> */
    public function findByUserId(UserId $userId): array
    {
        /** @var array<int, Consent> $consents */
        $consents = $this->em->getRepository(Consent::class)->findBy(['userId' => $userId]);

        return $consents;
    }
}
