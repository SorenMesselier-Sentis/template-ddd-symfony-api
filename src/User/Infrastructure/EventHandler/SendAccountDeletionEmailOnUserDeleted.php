<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventHandler;

use App\Shared\Domain\Exception\EmailDeliveryException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Service\Email\EmailMessage;
use App\Shared\Domain\Service\Email\EmailSenderInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Event\UserDeleted;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class SendAccountDeletionEmailOnUserDeleted
{
    public function __construct(
        private readonly EmailSenderInterface $emailService,
        private readonly UserRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(UserDeleted $event): void
    {
        $this->logger->info('Handling UserDeleted event', [
            'userId' => $event->aggregateId(),
        ]);

        $user = $this->repository->findById(
            UserId::fromString($event->aggregateId())
        );

        if (null === $user) {
            $this->logger->warning('User not found when handling UserDeleted event', [
                'userId' => $event->aggregateId(),
            ]);

            return;
        }

        try {
            $this->emailService->send(
                EmailMessage::create(
                    to: Email::fromString($user->email()->value()),
                    subject: 'Your account has been deleted',
                    textBody: $this->buildTextBody($user->email()->value()),
                    htmlBody: $this->buildHtmlBody($user->email()->value()),
                )
            );

            $this->logger->info('Account deletion email sent', [
                'userId' => $event->aggregateId(),
                'email' => $user->email()->value(),
            ]);
        } catch (EmailDeliveryException $e) {
            $this->logger->error('Failed to send account deletion email', [
                'userId' => $event->aggregateId(),
                'email' => $user->email()->value(),
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function buildTextBody(string $email): string
    {
        return sprintf(
            "Hello,\n\nYour account associated with %s has been successfully deleted.\n\nIf you did not request this, please contact our support team immediately.\n\nBest regards,\nThe Team",
            $email,
        );
    }

    private function buildHtmlBody(string $email): string
    {
        return sprintf(
            '<h1>Account Deleted</h1><p>Your account associated with <strong>%s</strong> has been successfully deleted.</p><p>If you did not request this, please contact our support team immediately.</p><p>Best regards,<br>The Team</p>',
            htmlspecialchars($email),
        );
    }
}
