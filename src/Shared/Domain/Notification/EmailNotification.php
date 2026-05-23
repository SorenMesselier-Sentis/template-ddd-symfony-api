<?php

declare(strict_types=1);

namespace App\Shared\Domain\Notification;

use App\Shared\Domain\Email\EmailMessage;
use App\Shared\Domain\ValueObject\Email;

final readonly class EmailNotification implements Notification
{
    public function __construct(
        private Email $recipientEmail,
        private string $subject,
        private string $body,
        private ?string $htmlBody = null,
        private ?Email $from = null,
    ) {
    }

    public static function create(
        Email $recipientEmail,
        string $subject,
        string $body,
        ?string $htmlBody = null,
        ?Email $from = null,
    ): self {
        return new self(
            recipientEmail: $recipientEmail,
            subject: $subject,
            body: $body,
            htmlBody: $htmlBody,
            from: $from,
        );
    }

    public function channel(): NotificationChannel
    {
        return NotificationChannel::EMAIL;
    }

    public function recipientEmail(): Email
    {
        return $this->recipientEmail;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function htmlBody(): ?string
    {
        return $this->htmlBody;
    }

    public function from(): ?Email
    {
        return $this->from;
    }

    public function hasHtmlBody(): bool
    {
        return null !== $this->htmlBody;
    }

    public function hasFrom(): bool
    {
        return null !== $this->from;
    }

    public function toEmailMessage(): EmailMessage
    {
        return EmailMessage::create(
            to: $this->recipientEmail,
            subject: $this->subject,
            textBody: $this->body,
            htmlBody: $this->htmlBody,
            from: $this->from,
        );
    }
}
