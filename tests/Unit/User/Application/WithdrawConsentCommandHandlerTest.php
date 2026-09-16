<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserIdMother;
use App\User\Application\Command\WithdrawConsent\WithdrawConsentCommand;
use App\User\Application\Command\WithdrawConsent\WithdrawConsentCommandHandler;
use App\User\Domain\Entity\Consent;
use App\User\Domain\Repository\ConsentRepositoryInterface;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\ConsentId;
use App\User\Domain\ValueObject\ConsentType;
use PHPUnit\Framework\MockObject\MockObject;

final class WithdrawConsentCommandHandlerTest extends UnitTestCase
{
    /** @var ConsentRepositoryInterface&MockObject */
    private ConsentRepositoryInterface $repository;

    /** @var UserContextInterface&MockObject */
    private UserContextInterface $userContext;

    private WithdrawConsentCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ConsentRepositoryInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);
        $this->handler = new WithdrawConsentCommandHandler($this->repository, $this->userContext);
    }

    public function testItWithdrawsAnExistingConsent(): void
    {
        $userId = UserIdMother::random();
        $consent = Consent::give(
            id: ConsentId::random(),
            userId: $userId,
            type: ConsentType::TERMS_OF_SERVICE,
            version: '2026-09-16',
        );

        $this->userContext->expects($this->once())->method('userId')->willReturn($userId);
        $this->repository->expects($this->once())->method('findByUserIdAndType')->willReturn($consent);
        $this->repository->expects($this->once())->method('save')->with($consent);

        ($this->handler)(new WithdrawConsentCommand(ConsentType::TERMS_OF_SERVICE->value));

        $this->assertFalse($consent->isActive());
    }

    public function testItIsIdempotentWhenNoConsentExists(): void
    {
        $userId = UserIdMother::random();

        $this->userContext->expects($this->once())->method('userId')->willReturn($userId);
        $this->repository->expects($this->once())->method('findByUserIdAndType')->willReturn(null);
        $this->repository->expects($this->never())->method('save');

        ($this->handler)(new WithdrawConsentCommand(ConsentType::TERMS_OF_SERVICE->value));
    }
}
