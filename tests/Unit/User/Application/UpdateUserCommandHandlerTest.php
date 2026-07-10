<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\EmailMother;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Command\UpdateUser\UpdateUserCommand;
use App\User\Application\Command\UpdateUser\UpdateUserCommandHandler;
use App\User\Application\Security\UserOwnershipGuard;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateUserCommandHandlerTest extends UnitTestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    private EventBusInterface $eventBus;

    private LoggerInterface $logger;
    private UserOwnershipGuard $ownershipGuard;
    private UpdateUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->eventBus = $this->createStub(EventBusInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);
        $userContext = $this->createStub(UserContextInterface::class);
        $userContext->method('roles')->willReturn([UserRole::ADMIN]);
        $this->ownershipGuard = new UserOwnershipGuard($userContext);

        $this->handler = new UpdateUserCommandHandler(
            $this->repository,
            $this->eventBus,
            $this->logger,
            $this->ownershipGuard,
        );
    }

    public function testItUpdatesUserName(): void
    {
        $user = UserMother::create();
        $command = new UpdateUserCommand(
            id: $user->id()->value(),
            firstName: 'Jane',
            lastName: 'Smith',
        );

        /** @var EventBusInterface&MockObject $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);
        $handler = new UpdateUserCommandHandler(
            $this->repository,
            $eventBus,
            $this->logger,
            $this->ownershipGuard,
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->repository
            ->expects($this->once())
            ->method('save');

        $eventBus
            ->expects($this->once())
            ->method('publish');

        ($handler)($command);

        $this->assertEquals('jane', $user->firstName()->value());
        $this->assertEquals('smith', $user->lastName()->value());
    }

    public function testItUpdatesUserEmail(): void
    {
        $user = UserMother::create();
        $command = new UpdateUserCommand(
            id: $user->id()->value(),
            email: 'updated@example.com',
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->repository
            ->expects($this->once())
            ->method('existsByEmail')
            ->willReturn(false);

        $this->repository
            ->expects($this->once())
            ->method('save');

        ($this->handler)($command);

        $this->assertEquals('updated@example.com', $user->email()->value());
    }

    public function testItThrowsWhenUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);

        $command = new UpdateUserCommand(
            id: UserMother::create()->id()->value(),
            firstName: 'Jane',
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testItThrowsWhenEmailAlreadyExists(): void
    {
        $this->expectException(UserAlreadyExistsException::class);

        $user = UserMother::create(email: EmailMother::create('current@example.com'));
        $command = new UpdateUserCommand(
            id: $user->id()->value(),
            email: 'taken@example.com',
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->repository
            ->expects($this->once())
            ->method('existsByEmail')
            ->willReturn(true);

        $this->repository
            ->expects($this->never())
            ->method('save');

        ($this->handler)($command);
    }
}
