<?php

declare(strict_types=1);

namespace App\Shared\Domain\Email;

final readonly class RenderedEmailContent
{
    public function __construct(
        private string $subject,
        private string $textBody,
        private string $htmlBody,
    ) {
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function textBody(): string
    {
        return $this->textBody;
    }

    public function htmlBody(): string
    {
        return $this->htmlBody;
    }
}
