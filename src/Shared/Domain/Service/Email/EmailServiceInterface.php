<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service\Email;

use App\Shared\Domain\Service\Email\EmailMessage;

interface EmailServiceInterface
{
    public function send(EmailMessage $message): void;
}
