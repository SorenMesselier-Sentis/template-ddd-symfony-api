<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\EmailMother;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Command\RequestPasswordReset\RequestPasswordResetCommand;
use App\User\Application\Command\RequestPasswordReset\RequestPasswordResetCommandHandler;
use App\User\Domain\Event\PasswordResetRequested;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class RequestPasswordResetCommandHandlerTest extends UnitTestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $userRepository;

    /** @var PasswordResetTokenRepositoryInterface&MockObject */
    private PasswordResetTokenRepositoryInterface $tokenRepository;

    /** @var EventBusInterface&MockObject */
    private EventBusInterface $eventBus;

    private RequestPasswordResetCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->handler = new RequestPasswordResetCommandHandler(
            $this->userRepository,
            $this->tokenRepository,
            $this->eventBus,
            $this->createStub(LoggerInterface::class),
        );
    }

    public function testItPublishesPasswordResetRequestedForExistingUser(): void
    {
        $user = UserMother::create();
        $command = new RequestPasswordResetCommand(EmailMother::create($user->email()->value()));

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);
        $this->tokenRepository->expects($this->once())->method('revokeAllForUser');
        $this->tokenRepository->expects($this->once())->method('save');
        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(PasswordResetRequested::class));

        ($this->handler)($command);
    }

    public function testItDoesNothingWhenUserNotFound(): void
    {
        $command = new RequestPasswordResetCommand(EmailMother::random());

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);
        $this->tokenRepository->expects($this->never())->method('save');
        $this->eventBus->expects($this->never())->method('publish');

        ($this->handler)($command);
    }
}
