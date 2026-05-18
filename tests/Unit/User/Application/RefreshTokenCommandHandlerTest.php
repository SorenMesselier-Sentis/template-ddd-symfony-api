<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\RefreshTokenMother;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Command\RefreshToken\RefreshTokenCommand;
use App\User\Application\Command\RefreshToken\RefreshTokenCommandHandler;
use App\User\Domain\Exception\InactiveAccountException;
use App\User\Domain\Exception\InvalidTokenException;
use App\User\Domain\Exception\TokenExpiredException;
use App\User\Domain\Exception\TokenRevokedException;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Service\TokenServiceInterface;
use App\User\Domain\ValueObject\AccessToken;
use App\User\Domain\ValueObject\RefreshToken as RefreshTokenValueObject;
use PHPUnit\Framework\MockObject\MockObject;

final class RefreshTokenCommandHandlerTest extends UnitTestCase
{
    /** @var RefreshTokenRepositoryInterface&MockObject */
    private RefreshTokenRepositoryInterface $refreshTokenRepository;

    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $userRepository;

    /** @var TokenServiceInterface&MockObject */
    private TokenServiceInterface $tokenService;

    private LoggerInterface $logger;
    private RefreshTokenCommandHandler $handler;

    protected function setUp(): void
    {
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenService = $this->createMock(TokenServiceInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->handler = new RefreshTokenCommandHandler(
            $this->refreshTokenRepository,
            $this->userRepository,
            $this->tokenService,
            $this->logger,
        );
    }

    public function testItRefreshesTokens(): void
    {
        $user = UserMother::create();
        $storedToken = RefreshTokenMother::create(
            userId: $user->id(),
            token: 'valid-refresh-token',
        );
        $command = new RefreshTokenCommand(refreshToken: 'valid-refresh-token');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->with('valid-refresh-token')
            ->willReturn($storedToken);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->id())
            ->willReturn($user);

        $this->tokenService
            ->expects($this->once())
            ->method('generateAccessToken')
            ->with($user)
            ->willReturn(new AccessToken('new-access-token', 3600));

        $this->tokenService
            ->expects($this->once())
            ->method('generateRefreshToken')
            ->with($user)
            ->willReturn(new RefreshTokenValueObject('new-refresh-token', 2592000));

        $this->refreshTokenRepository
            ->expects($this->exactly(2))
            ->method('save');

        $response = ($this->handler)($command);

        $this->assertTrue($storedToken->isRevoked());
        $this->assertEquals('new-access-token', $response->accessToken);
        $this->assertEquals('new-refresh-token', $response->refreshToken);
    }

    public function testItThrowsWhenTokenNotFound(): void
    {
        $this->expectException(InvalidTokenException::class);

        $command = new RefreshTokenCommand(refreshToken: 'unknown-token');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testItThrowsWhenTokenIsRevoked(): void
    {
        $this->expectException(TokenRevokedException::class);

        $storedToken = RefreshTokenMother::create(token: 'revoked-token');
        $storedToken->revoke();
        $command = new RefreshTokenCommand(refreshToken: 'revoked-token');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->willReturn($storedToken);

        ($this->handler)($command);
    }

    public function testItThrowsWhenTokenIsExpired(): void
    {
        $this->expectException(TokenExpiredException::class);

        $storedToken = RefreshTokenMother::expired();
        $command = new RefreshTokenCommand(refreshToken: $storedToken->token());

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->willReturn($storedToken);

        ($this->handler)($command);
    }

    public function testItThrowsWhenUserNotFound(): void
    {
        $this->expectException(InvalidTokenException::class);

        $storedToken = RefreshTokenMother::create(token: 'orphan-token');
        $command = new RefreshTokenCommand(refreshToken: 'orphan-token');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->willReturn($storedToken);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save');

        ($this->handler)($command);
    }

    public function testItThrowsWhenAccountIsInactive(): void
    {
        $this->expectException(InactiveAccountException::class);

        $user = UserMother::create();
        $user->delete();
        $user->pullDomainEvents();

        $storedToken = RefreshTokenMother::create(
            userId: $user->id(),
            token: 'inactive-account-token',
        );
        $command = new RefreshTokenCommand(refreshToken: 'inactive-account-token');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->willReturn($storedToken);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save');

        ($this->handler)($command);
    }
}
