<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Exception\RefreshTokenNotFoundException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\RefreshTokenMother;
use App\User\Application\Command\LogoutUser\LogoutUserCommand;
use App\User\Application\Command\LogoutUser\LogoutUserCommandHandler;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class LogoutUserCommandHandlerTest extends UnitTestCase
{
    /** @var RefreshTokenRepositoryInterface&MockObject */
    private RefreshTokenRepositoryInterface $refreshTokenRepository;

    private LoggerInterface $logger;
    private LogoutUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->handler = new LogoutUserCommandHandler(
            $this->refreshTokenRepository,
            $this->logger,
        );
    }

    public function testItRevokesRefreshToken(): void
    {
        $storedToken = RefreshTokenMother::create(token: 'my-refresh-token');
        $command = new LogoutUserCommand(refreshToken: 'my-refresh-token');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->with('my-refresh-token')
            ->willReturn($storedToken);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($storedToken);

        ($this->handler)($command);

        $this->assertTrue($storedToken->isRevoked());
    }

    public function testItThrowsWhenRefreshTokenNotFound(): void
    {
        $this->expectException(RefreshTokenNotFoundException::class);

        $command = new LogoutUserCommand(refreshToken: 'unknown-token');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->willReturn(null);

        ($this->handler)($command);
    }
}
