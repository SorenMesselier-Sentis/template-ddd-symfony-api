<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventHandler;

use App\Shared\Domain\Email\EmailTemplateRendererInterface;
use App\Shared\Domain\Exception\EmailDeliveryException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Domain\Notification\EmailNotification;
use App\Shared\Domain\Notification\NotificationSenderInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Event\PasswordResetRequested;
use App\User\Infrastructure\Email\UserEmailTemplate;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class SendPasswordResetEmailOnPasswordResetRequested
{
    public function __construct(
        private readonly NotificationSenderInterface $notificationSender,
        private readonly EmailTemplateRendererInterface $emailTemplateRenderer,
        private readonly LoggerInterface $logger,
        private readonly MetricsCollectorInterface $metrics,
        private readonly string $frontendUrl,
    ) {
    }

    public function __invoke(PasswordResetRequested $event): void
    {
        $this->logger->info('Sending password reset email', [
            'userId' => $event->aggregateId(),
            'email' => $event->email,
        ]);

        try {
            $resetUrl = rtrim($this->frontendUrl, '/').'/reset-password?token='.urlencode($event->token);

            $content = $this->emailTemplateRenderer->render(
                UserEmailTemplate::PASSWORD_RESET,
                [
                    'firstName' => $event->firstName,
                    'resetUrl' => $resetUrl,
                ],
            );

            $this->notificationSender->send(
                EmailNotification::create(
                    recipientEmail: Email::fromString($event->email),
                    subject: $content->subject(),
                    body: $content->textBody(),
                    htmlBody: $content->htmlBody(),
                ),
            );

            $this->metrics->incrementCounter('emails_sent_total', [
                'template' => 'password_reset',
                'status' => 'sent',
            ]);
        } catch (EmailDeliveryException $e) {
            $this->logger->error('Failed to send password reset email', [
                'userId' => $event->aggregateId(),
                'exception' => $e->getMessage(),
            ]);
            $this->metrics->incrementCounter('emails_sent_total', [
                'template' => 'password_reset',
                'status' => 'failed',
            ]);

            throw $e;
        }
    }
}
