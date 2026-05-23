<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventHandler;

use App\Shared\Domain\Exception\EmailDeliveryException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Service\Email\EmailMessage;
use App\Shared\Domain\Service\Email\EmailSenderInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Event\UserCreated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class SendWelcomeEmailOnUserCreated
{
    public function __construct(
        private readonly EmailSenderInterface $emailService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(UserCreated $event): void
    {
        $this->logger->info('Sending welcome email', [
            'userId' => $event->aggregateId(),
            'email' => $event->email,
        ]);

        try {
            $this->emailService->send(
                EmailMessage::create(
                    to: Email::fromString($event->email),
                    subject: 'Welcome to the platform!',
                    textBody: $this->buildTextBody($event),
                    htmlBody: $this->buildHtmlBody($event),
                )
            );

            $this->logger->info('Welcome email sent', [
                'userId' => $event->aggregateId(),
            ]);
        } catch (EmailDeliveryException $e) {
            $this->logger->error('Failed to send welcome email', [
                'userId' => $event->aggregateId(),
                'email' => $event->email,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function buildTextBody(UserCreated $event): string
    {
        return sprintf(
            "Hello %s %s,\n\nWelcome to the platform! Your account is now active.\n\nBest regards,\nThe Team",
            $event->firstName,
            $event->lastName,
        );
    }

    private function buildHtmlBody(UserCreated $event): string
    {
        return sprintf(
            '<h1>Hello %s %s!</h1><p>Welcome to the platform! Your account is now active.</p><p>Best regards,<br>The Team</p>',
            htmlspecialchars($event->firstName),
            htmlspecialchars($event->lastName),
        );
    }
}
