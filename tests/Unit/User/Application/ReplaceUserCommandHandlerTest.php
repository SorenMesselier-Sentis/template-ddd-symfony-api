<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\EmailMother;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Command\ReplaceUser\ReplaceUserCommand;
use App\User\Application\Command\ReplaceUser\ReplaceUserCommandHandler;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class ReplaceUserCommandHandlerTest extends UnitTestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    private EventBusInterface $eventBus;

    private LoggerInterface $logger;
    private ReplaceUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->eventBus = $this->createStub(EventBusInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->handler = new ReplaceUserCommandHandler(
            $this->repository,
            $this->eventBus,
            $this->logger,
        );
    }

    public function testItReplacesAUser(): void
    {
        $user = UserMother::create();
        $command = new ReplaceUserCommand(
            id: $user->id()->value(),
            firstName: 'Alice',
            lastName: 'Wonder',
            email: 'alice@example.com',
            password: 'newpassword1',
        );

        /** @var EventBusInterface&MockObject $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);
        $handler = new ReplaceUserCommandHandler(
            $this->repository,
            $eventBus,
            $this->logger,
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

        $eventBus
            ->expects($this->once())
            ->method('publish');

        ($handler)($command);

        $this->assertEquals('alice', $user->firstName()->value());
        $this->assertEquals('alice@example.com', $user->email()->value());
        $this->assertTrue($user->password()->verify('newpassword1'));
    }

    public function testItThrowsWhenUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);

        $command = new ReplaceUserCommand(
            id: UserMother::create()->id()->value(),
            firstName: 'Alice',
            lastName: 'Wonder',
            email: 'alice@example.com',
            password: 'newpassword1',
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
        $command = new ReplaceUserCommand(
            id: $user->id()->value(),
            firstName: 'Alice',
            lastName: 'Wonder',
            email: 'taken@example.com',
            password: 'newpassword1',
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
