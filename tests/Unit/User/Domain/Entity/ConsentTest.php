<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserIdMother;
use App\User\Domain\Entity\Consent;
use App\User\Domain\ValueObject\ConsentId;
use App\User\Domain\ValueObject\ConsentType;

final class ConsentTest extends UnitTestCase
{
    public function testItGivesConsent(): void
    {
        $userId = UserIdMother::random();

        $consent = Consent::give(
            id: ConsentId::random(),
            userId: $userId,
            type: ConsentType::TERMS_OF_SERVICE,
            version: '2026-09-16',
        );

        $this->assertTrue($userId->equals($consent->userId()));
        $this->assertEquals(ConsentType::TERMS_OF_SERVICE, $consent->type());
        $this->assertEquals('2026-09-16', $consent->version());
        $this->assertTrue($consent->isActive());
        $this->assertNull($consent->withdrawnAt());
    }

    public function testItWithdrawsConsent(): void
    {
        $consent = Consent::give(
            id: ConsentId::random(),
            userId: UserIdMother::random(),
            type: ConsentType::MARKETING_EMAILS,
            version: '2026-09-16',
        );

        $consent->withdraw();

        $this->assertFalse($consent->isActive());
        $this->assertNotNull($consent->withdrawnAt());
    }

    public function testItRenewsConsentAfterWithdrawal(): void
    {
        $consent = Consent::give(
            id: ConsentId::random(),
            userId: UserIdMother::random(),
            type: ConsentType::PRIVACY_POLICY,
            version: '2026-09-16',
        );
        $consent->withdraw();

        $consent->renew('2027-01-01');

        $this->assertTrue($consent->isActive());
        $this->assertNull($consent->withdrawnAt());
        $this->assertEquals('2027-01-01', $consent->version());
    }
}
