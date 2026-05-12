<?php

declare(strict_types=1);

namespace App\User\Application\Command\LoginUser;

use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Service\TokenServiceInterface;
use App\User\Domain\ValueObject\UserStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class LoginUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly TokenServiceInterface $tokenService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(LoginUserCommand $command): LoginUserResponse
    {
        $this->logger->info('User login attempt', ['email' => $command->email]);

        $user = $this->repository->findByEmail($command->email);

        if (null === $user) {
            throw InvalidCredentialsException::create();
        }

        if (!in_array($user->status(), [UserStatus::ACTIVE], true)) {
            throw InvalidCredentialsException::create();
        }

        if (!$user->password()->verify($command->password)) {
            throw InvalidCredentialsException::create();
        }

        $accessToken = $this->tokenService->generateAccessToken($user);
        $refreshToken = $this->tokenService->generateRefreshToken($user);

        $this->logger->info('User logged in', ['id' => $user->id()->value()]);

        return new LoginUserResponse(
            accessToken: $accessToken->value(),
            accessTokenExpiresIn: $accessToken->expiresIn(),
            refreshToken: $refreshToken->value(),
            refreshTokenExpiresIn: $refreshToken->expiresIn(),
        );
    }
}
