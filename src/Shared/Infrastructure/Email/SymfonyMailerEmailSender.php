<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Email;

use App\Shared\Domain\Email\EmailMessage;
use App\Shared\Domain\Email\EmailSenderInterface;
use App\Shared\Domain\Exception\EmailDeliveryException;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as SymfonyEmail;

final class SymfonyMailerEmailSender implements EmailSenderInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $defaultFrom,
    ) {
    }

    public function send(EmailMessage $message): void
    {
        $symfonyEmail = $this->buildSymfonyEmail($message);

        try {
            $this->mailer->send($symfonyEmail);

            $this->logger->info('Email sent', [
                'to' => $message->to()->value(),
                'subject' => $message->subject(),
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Email delivery failed', [
                'to' => $message->to()->value(),
                'subject' => $message->subject(),
                'exception' => $e->getMessage(),
            ]);

            throw EmailDeliveryException::create(recipient: $message->to()->value(), previous: $e);
        }
    }

    private function buildSymfonyEmail(EmailMessage $message): SymfonyEmail
    {
        $from = $message->hasFrom()
            ? $message->from()->value()
            : $this->defaultFrom;

        $email = (new SymfonyEmail())
            ->from($from)
            ->to($message->to()->value())
            ->subject($message->subject())
            ->text($message->textBody());

        if ($message->hasHtmlBody()) {
            $email->html($message->htmlBody());
        }

        return $email;
    }
}
