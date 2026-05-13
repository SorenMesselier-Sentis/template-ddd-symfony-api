<?php

declare(strict_types=1);

namespace App\User\Application\Command\RefreshToken;

use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Application\Command\LoginUser\LoginUserResponse;
use App\User\Domain\Entity\RefreshToken as RefreshTokenEntity;
use App\User\Domain\Exception\InactiveAccountException;
use App\User\Domain\Exception\InvalidTokenException;
use App\User\Domain\Exception\TokenExpiredException;
use App\User\Domain\Exception\TokenRevokedException;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Service\TokenServiceInterface;
use App\User\Domain\ValueObject\RefreshTokenId;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RefreshTokenCommandHandler
{
    public function __construct(
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly UserRepositoryInterface $userRepositoryInterface,
        private readonly TokenServiceInterface $tokenService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(RefreshTokenCommand $command): LoginUserResponse
    {
        $this->logger->info('Refresh token attempt');

        $storedToken = $this->refreshTokenRepository->findByToken($command->refreshToken);

        if (null === $storedToken) {
            throw InvalidTokenException::create();
        }

        if ($storedToken->isRevoked()) {
            throw TokenRevokedException::create();
        }

        if ($storedToken->isExpired()) {
            throw TokenExpiredException::create();
        }

        $user = $this->userRepositoryInterface->findById($storedToken->userId());

        if (null === $user) {
            $storedToken->revoke();
            $this->refreshTokenRepository->save($storedToken);

            throw InvalidTokenException::create();
        }

        if (UserStatus::ACTIVE !== $user->status()) {
            $storedToken->revoke();
            $this->refreshTokenRepository->save($storedToken);

            throw InactiveAccountException::create();
        }

        $storedToken->revoke();
        $this->refreshTokenRepository->save($storedToken);

        $newAccessToken = $this->tokenService->generateAccessToken($user);
        $newRefreshToken = $this->tokenService->generateRefreshToken($user);

        $this->persistRefreshToken(
            $storedToken->userId(),
            $newRefreshToken->value(),
            $newRefreshToken->expiresIn(),
        );

        $this->logger->info('Token refreshed', ['userId' => $user->id()->value()]);

        return new LoginUserResponse(
            accessToken: $newAccessToken->value(),
            accessTokenExpiresIn: $newAccessToken->expiresIn(),
            refreshToken: $newRefreshToken->value(),
            refreshTokenExpiresIn: $newRefreshToken->expiresIn(),
        );
    }

    private function persistRefreshToken(UserId $userId, string $token, int $expiresInSeconds): void
    {
        $entity = RefreshTokenEntity::create(
            id: RefreshTokenId::random(),
            userId: $userId,
            token: $token,
            expiresAt: new \DateTimeImmutable(sprintf('+%d seconds', $expiresInSeconds)),
        );

        $this->refreshTokenRepository->save($entity);
    }
}
