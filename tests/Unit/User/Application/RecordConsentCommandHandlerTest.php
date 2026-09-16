<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserIdMother;
use App\User\Application\Command\RecordConsent\RecordConsentCommand;
use App\User\Application\Command\RecordConsent\RecordConsentCommandHandler;
use App\User\Domain\Entity\Consent;
use App\User\Domain\Repository\ConsentRepositoryInterface;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\ConsentId;
use App\User\Domain\ValueObject\ConsentType;
use PHPUnit\Framework\MockObject\MockObject;

final class RecordConsentCommandHandlerTest extends UnitTestCase
{
    /** @var ConsentRepositoryInterface&MockObject */
    private ConsentRepositoryInterface $repository;

    /** @var UserContextInterface&MockObject */
    private UserContextInterface $userContext;

    private RecordConsentCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ConsentRepositoryInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);
        $this->handler = new RecordConsentCommandHandler($this->repository, $this->userContext);
    }

    public function testItGivesConsentWhenNoneExists(): void
    {
        $userId = UserIdMother::random();
        $command = new RecordConsentCommand(type: ConsentType::TERMS_OF_SERVICE->value, version: '2026-09-16');

        $this->userContext->expects($this->once())->method('userId')->willReturn($userId);
        $this->repository->expects($this->once())->method('findByUserIdAndType')->willReturn(null);

        $saved = null;
        $this->repository->expects($this->once())->method('save')
            ->willReturnCallback(function (Consent $consent) use (&$saved): void {
                $saved = $consent;
            });

        ($this->handler)($command);

        $this->assertInstanceOf(Consent::class, $saved);
        $this->assertEquals(ConsentType::TERMS_OF_SERVICE, $saved->type());
        $this->assertEquals('2026-09-16', $saved->version());
        $this->assertTrue($saved->isActive());
    }

    public function testItRenewsExistingConsent(): void
    {
        $userId = UserIdMother::random();
        $existing = Consent::give(
            id: ConsentId::random(),
            userId: $userId,
            type: ConsentType::MARKETING_EMAILS,
            version: '2025-01-01',
        );
        $existing->withdraw();

        $command = new RecordConsentCommand(type: ConsentType::MARKETING_EMAILS->value, version: '2026-09-16');

        $this->userContext->expects($this->once())->method('userId')->willReturn($userId);
        $this->repository->expects($this->once())->method('findByUserIdAndType')->willReturn($existing);
        $this->repository->expects($this->once())->method('save')->with($existing);

        ($this->handler)($command);

        $this->assertTrue($existing->isActive());
        $this->assertEquals('2026-09-16', $existing->version());
    }
}
