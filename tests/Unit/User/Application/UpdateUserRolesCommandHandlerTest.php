<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Command\UpdateUserRoles\UpdateUserRolesCommand;
use App\User\Application\Command\UpdateUserRoles\UpdateUserRolesCommandHandler;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateUserRolesCommandHandlerTest extends UnitTestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    private EventBusInterface $eventBus;

    private LoggerInterface $logger;
    private UpdateUserRolesCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->eventBus = $this->createStub(EventBusInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->handler = new UpdateUserRolesCommandHandler(
            $this->repository,
            $this->eventBus,
            $this->logger,
        );
    }

    public function testItUpdatesUserRoles(): void
    {
        $user = UserMother::create();
        $command = new UpdateUserRolesCommand(
            id: $user->id()->value(),
            roles: [UserRole::ADMIN->value, UserRole::USER->value],
        );

        /** @var EventBusInterface&MockObject $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);
        $handler = new UpdateUserRolesCommandHandler(
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
            ->method('save');

        $eventBus
            ->expects($this->once())
            ->method('publish');

        ($handler)($command);

        $this->assertEquals([UserRole::ADMIN, UserRole::USER], $user->roles());
    }

    public function testItAppendsUserRoleWhenMissing(): void
    {
        $user = UserMother::create();
        $command = new UpdateUserRolesCommand(
            id: $user->id()->value(),
            roles: [UserRole::ADMIN->value],
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->repository
            ->expects($this->once())
            ->method('save');

        ($this->handler)($command);

        $this->assertEquals([UserRole::ADMIN, UserRole::USER], $user->roles());
    }

    public function testItThrowsWhenUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);

        $command = new UpdateUserRolesCommand(
            id: UserMother::create()->id()->value(),
            roles: [UserRole::ADMIN->value],
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->repository
            ->expects($this->never())
            ->method('save');

        ($this->handler)($command);
    }
}
