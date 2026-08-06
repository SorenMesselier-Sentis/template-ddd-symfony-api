<?php

declare(strict_types=1);

namespace App\Shared\Domain\Privacy;

/**
 * Implemented once per bounded context that holds personal data about a user
 * (GDPR right of access / data portability). Each implementation is
 * auto-tagged (see config/services.yaml) and collected generically — no
 * bounded context needs to know about the others.
 */
interface PersonalDataExporterInterface
{
    /**
     * Top-level key this exporter's data is nested under in the export payload.
     */
    public function key(): string;

    /**
     * @return array<int|string, mixed>
     */
    public function export(string $subjectId): array;
}
