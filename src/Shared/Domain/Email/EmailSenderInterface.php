<?php

declare(strict_types=1);

namespace App\Shared\Domain\Email;

interface EmailSenderInterface
{
    public function send(EmailMessage $message): void;
}
