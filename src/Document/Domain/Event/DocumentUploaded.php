<?php

declare(strict_types=1);

namespace App\Document\Domain\Event;

use App\Document\Domain\Enum\UploadResultStatus;
use App\Shared\Domain\Bus\Event\DomainEvent;

final class DocumentUploaded extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public readonly string $ownerId,
        public readonly string $bucketName,
        public readonly string $objectPath,
        public readonly string $originalName,
        public readonly int $size,
        public readonly string $mimeType,
        public readonly UploadResultStatus $status,
    ) {
        parent::__construct($aggregateId);
    }

    public static function eventName(): string
    {
        return 'document.uploaded';
    }
}
