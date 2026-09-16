<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure;

use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Unit\User\Domain\Mother\UserIdMother;
use App\User\Domain\Entity\Consent;
use App\User\Domain\ValueObject\ConsentId;
use App\User\Domain\ValueObject\ConsentType;
use App\User\Infrastructure\Persistence\Doctrine\Repository\DoctrineConsentRepository;

final class DoctrineConsentRepositoryTest extends IntegrationTestCase
{
    private DoctrineConsentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em->getConnection()->executeStatement('TRUNCATE TABLE consents RESTART IDENTITY CASCADE');
        $this->repository = new DoctrineConsentRepository($this->em);
    }

    public function testItSavesAndFindsByUserIdAndType(): void
    {
        $userId = UserIdMother::random();
        $consent = Consent::give(
            id: ConsentId::random(),
            userId: $userId,
            type: ConsentType::TERMS_OF_SERVICE,
            version: '2026-09-16',
        );

        $this->repository->save($consent);
        $this->em->clear();

        $found = $this->repository->findByUserIdAndType($userId, ConsentType::TERMS_OF_SERVICE);

        $this->assertNotNull($found);
        $this->assertEquals('2026-09-16', $found->version());
    }

    public function testItReturnsNullWhenNoConsentOfThatType(): void
    {
        $found = $this->repository->findByUserIdAndType(UserIdMother::random(), ConsentType::MARKETING_EMAILS);

        $this->assertNull($found);
    }

    public function testItFindsAllConsentsForAUser(): void
    {
        $userId = UserIdMother::random();

        $this->repository->save(Consent::give(ConsentId::random(), $userId, ConsentType::TERMS_OF_SERVICE, '2026-09-16'));
        $this->repository->save(Consent::give(ConsentId::random(), $userId, ConsentType::PRIVACY_POLICY, '2026-09-16'));
        $this->repository->save(Consent::give(ConsentId::random(), UserIdMother::random(), ConsentType::TERMS_OF_SERVICE, '2026-09-16'));

        $this->em->clear();

        $found = $this->repository->findByUserId($userId);

        $this->assertCount(2, $found);
    }
}
