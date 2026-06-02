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
use App\User\Domain\Event\UserDeleted;
use App\User\Infrastructure\Email\UserEmailTemplate;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class SendAccountDeletionEmailOnUserDeleted
{
    public function __construct(
        private readonly NotificationSenderInterface $notificationSender,
        private readonly EmailTemplateRendererInterface $emailTemplateRenderer,
        private readonly LoggerInterface $logger,
        private readonly MetricsCollectorInterface $metrics,
    ) {
    }

    public function __invoke(UserDeleted $event): void
    {
        $this->logger->info('Sending account deletion email', [
            'userId' => $event->aggregateId(),
            'email' => $event->email,
        ]);

        try {
            $content = $this->emailTemplateRenderer->render(
                UserEmailTemplate::ACCOUNT_DELETION,
                ['email' => $event->email],
            );

            $this->notificationSender->send(
                EmailNotification::create(
                    recipientEmail: Email::fromString($event->email),
                    subject: $content->subject(),
                    body: $content->textBody(),
                    htmlBody: $content->htmlBody(),
                ),
            );

            $this->logger->info('Account deletion email sent', [
                'userId' => $event->aggregateId(),
                'email' => $event->email,
            ]);
            $this->metrics->incrementCounter('emails_sent_total', [
                'template' => 'account_deletion',
                'status' => 'sent',
            ]);
        } catch (EmailDeliveryException $e) {
            $this->logger->error('Failed to send account deletion email', [
                'userId' => $event->aggregateId(),
                'email' => $event->email,
                'exception' => $e->getMessage(),
            ]);
            $this->metrics->incrementCounter('emails_sent_total', [
                'template' => 'account_deletion',
                'status' => 'failed',
            ]);

            throw $e;
        }
    }
}
