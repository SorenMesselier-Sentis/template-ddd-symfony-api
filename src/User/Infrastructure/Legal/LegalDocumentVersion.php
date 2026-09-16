<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Legal;

/**
 * Current version of each legal document a user can consent to (see
 * docs/legal/*.md, whose front matter must be bumped in the same change as
 * this file). {@see \App\User\Application\Command\RecordConsent\RecordConsentCommand}
 * records the version a user actually consented to, so a client can compare
 * it against the current one here to detect a stale consent.
 */
final class LegalDocumentVersion
{
    public const TERMS_OF_SERVICE = '2026-09-16';

    public const PRIVACY_POLICY = '2026-09-16';
}
