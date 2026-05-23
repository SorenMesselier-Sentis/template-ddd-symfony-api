<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service\Email;

use App\Shared\Domain\Service\Email\EmailMessage;

interface EmailSenderInterface
{
    public function send(EmailMessage $message): void;
}
