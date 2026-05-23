<?php

declare(strict_types=1);

namespace App\Shared\Domain\Email;

use App\Shared\Domain\ValueObject\Email;

final class EmailMessage
{
    private function __construct(
        private readonly Email $to,
        private readonly string $subject,
        private readonly string $textBody,
        private readonly ?string $htmlBody,
        private readonly ?Email $from,
    ) {
    }

    public static function create(
        Email $to,
        string $subject,
        string $textBody,
        ?string $htmlBody = null,
        ?Email $from = null,
    ): self {
        return new self(
            to: $to,
            subject: $subject,
            textBody: $textBody,
            htmlBody: $htmlBody,
            from: $from,
        );
    }

    public function to(): Email
    {
        return $this->to;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function textBody(): string
    {
        return $this->textBody;
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
}
