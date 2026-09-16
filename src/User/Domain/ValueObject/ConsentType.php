<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

enum ConsentType: string
{
    case TERMS_OF_SERVICE = 'terms_of_service';
    case PRIVACY_POLICY = 'privacy_policy';
    case MARKETING_EMAILS = 'marketing_emails';
}
