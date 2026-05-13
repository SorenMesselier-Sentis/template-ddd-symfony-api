<?php

declare(strict_types=1);

namespace App\User\Application\Command\LogoutUser;

use App\Shared\Domain\Exception\RefreshTokenNotFoundException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class LogoutUserCommandHandler
{
    public function __construct(
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(LogoutUserCommand $command): void
    {
        $this->logger->info('User logout attempt');

        $storedToken = $this->refreshTokenRepository->findByToken($command->refreshToken);

        if ($storedToken === null) {
            throw RefreshTokenNotFoundException::create();
        }

        $storedToken->revoke();
        $this->refreshTokenRepository->save($storedToken);

        $this->logger->info('User logged out', [
            'userId' => $storedToken->userId()->value(),
        ]);
    }
}
