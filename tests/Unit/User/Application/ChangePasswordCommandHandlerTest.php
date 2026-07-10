<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Command\ChangePassword\ChangePasswordCommand;
use App\User\Application\Command\ChangePassword\ChangePasswordCommandHandler;
use App\User\Domain\Exception\InvalidCurrentPasswordException;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\HashedPassword;
use PHPUnit\Framework\MockObject\MockObject;

final class ChangePasswordCommandHandlerTest extends UnitTestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $userRepository;

    /** @var UserContextInterface&MockObject */
    private UserContextInterface $userContext;

    private ChangePasswordCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);
        $this->handler = new ChangePasswordCommandHandler(
            $this->userRepository,
            $this->createStub(RefreshTokenRepositoryInterface::class),
            $this->userContext,
            $this->createStub(EventBusInterface::class),
            $this->createStub(LoggerInterface::class),
        );
    }

    public function testItChangesPasswordWhenCurrentPasswordIsValid(): void
    {
        $user = UserMother::create(password: HashedPassword::fromPlainPassword('oldsecret1'));
        $command = new ChangePasswordCommand('oldsecret1', 'newsecret1');

        $this->userContext->expects($this->once())->method('userId')->willReturn($user->id());
        $this->userRepository->expects($this->once())->method('findById')->willReturn($user);
        $this->userRepository->expects($this->once())->method('save');

        ($this->handler)($command);

        $this->assertTrue($user->password()->verify('newsecret1'));
    }

    public function testItThrowsWhenCurrentPasswordIsInvalid(): void
    {
        $this->expectException(InvalidCurrentPasswordException::class);

        $user = UserMother::create(password: HashedPassword::fromPlainPassword('oldsecret1'));
        $command = new ChangePasswordCommand('wrongpassword', 'newsecret1');

        $this->userContext->expects($this->once())->method('userId')->willReturn($user->id());
        $this->userRepository->expects($this->once())->method('findById')->willReturn($user);
        $this->userRepository->expects($this->never())->method('save');

        ($this->handler)($command);
    }
}
