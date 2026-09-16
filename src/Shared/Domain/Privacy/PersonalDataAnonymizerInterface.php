<?php

declare(strict_types=1);

namespace App\Shared\Domain\Privacy;

/**
 * Implemented by a bounded context that holds personal data about a user and
 * must purge/anonymize it once a soft-deleted record has passed the GDPR
 * retention window (storage limitation principle) — the automated
 * counterpart to {@see PersonalDataExporterInterface}. Each implementation is
 * auto-tagged (see config/services.yaml) and collected generically by the
 * scheduled cleanup — no bounded context needs to know about the others.
 */
interface PersonalDataAnonymizerInterface
{
    /**
     * Identifies this anonymizer in logs/metrics (e.g. "user").
     */
    public function key(): string;

    /**
     * Anonymizes every soft-deleted record whose deletion predates $before.
     *
     * @return int number of records anonymized
     */
    public function anonymizeExpired(\DateTimeImmutable $before): int;
}
